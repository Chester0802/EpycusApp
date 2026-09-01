<?php

declare(strict_types=1);

namespace App\Modules\Calendar\Infrastructure\Repositories;

use App\Modules\Calendar\Domain\Contracts\CalendarRepositoryInterface;
use App\Modules\Calendar\Infrastructure\Models\CourseModel;
use App\Modules\Calendar\Infrastructure\Models\CourseNoteModel;
use App\Modules\Calendar\Infrastructure\Models\CourseSessionModel;
use App\Modules\Calendar\Infrastructure\Models\HolidayModel;
use App\Shared\Domain\Contracts\CalendarReaderInterface;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

final class EloquentCalendarRepository implements CalendarReaderInterface, CalendarRepositoryInterface
{
    private const CACHE_KEY = 'calendar_holidays';

    private const CACHE_TTL = 86400;

    // ── Feriados ─────────────────────────────────────────────────────────────

    public function getHolidaysInMonth(int $year, int $month): Collection
    {
        $all = $this->getAllHolidays();

        return $all->filter(fn ($h) => (int) $h->date->format('m') === $month && (int) $h->date->format('Y') === $year)
            ->values();
    }

    /** @return Collection<int, HolidayModel> */
    private function getAllHolidays(): Collection
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
            return HolidayModel::query()->orderBy('date')->get();
        });
    }

    private function findByDate(string $date): ?HolidayModel
    {
        return $this->getAllHolidays()->first(fn ($h) => $h->date->toDateString() === $date);
    }

    // ── CalendarReaderInterface ───────────────────────────────────────────────

    public function isHoliday(\DateTimeImmutable $date): bool
    {
        return $this->findByDate($date->format('Y-m-d')) !== null;
    }

    public function isNonWorkingDay(\DateTimeImmutable $date): bool
    {
        return $this->isHoliday($date);
    }

    public function isExamWeek(\DateTimeImmutable $date): bool
    {
        $examWeeks = config('academic.current_cycle.exam_weeks', []);
        $check = Carbon::instance($date);

        foreach ($examWeeks as $week) {
            $from = Carbon::parse($week['from']);
            $to   = Carbon::parse($week['to']);

            if ($check->between($from, $to)) {
                return true;
            }
        }

        return false;
    }

    public function getHolidayName(\DateTimeImmutable $date): ?string
    {
        return $this->findByDate($date->format('Y-m-d'))?->name;
    }

    public function interventionDayFor(\DateTimeImmutable $date): ?int
    {
        $start = Carbon::parse(config('academic.current_cycle.starts_on', '2026-09-07'));
        $check = Carbon::instance($date);

        if ($check < $start) {
            return null;
        }

        return (int) $start->startOfDay()->diffInDays($check->startOfDay()) + 1;
    }

    // ── Cursos ───────────────────────────────────────────────────────────────

    /** @return Collection<int, CourseModel> */
    public function getCoursesForUser(int $userId): Collection
    {
        return CourseModel::query()
            ->where('user_id', $userId)
            ->with(['sessions'])
            ->orderBy('name')
            ->get();
    }

    /**
     * @param array<string, mixed> $data  {name, color, sessions: [{day_of_week, start_time, end_time, classroom}]}
     */
    public function createCourse(int $userId, array $data): CourseModel
    {
        $course = CourseModel::query()->create([
            'user_id'        => $userId,
            'name'           => $data['name'],
            'color'          => $data['color'] ?? 'primary',
            'starts_at'      => $data['starts_at'] ?? null,
            'ends_at'        => $data['ends_at'] ?? null,
            'professor'      => $data['professor'] ?? null,
            'credits'        => isset($data['credits']) && $data['credits'] !== '' ? (int) $data['credits'] : null,
            'target_grade'   => isset($data['target_grade']) && $data['target_grade'] !== '' ? (float) $data['target_grade'] : null,
            'min_pass_grade' => isset($data['min_pass_grade']) && $data['min_pass_grade'] !== '' ? (float) $data['min_pass_grade'] : null,
        ]);

        foreach ($data['sessions'] as $session) {
            CourseSessionModel::query()->create([
                'course_id'   => $course->id,
                'day_of_week' => (int) $session['day_of_week'],
                'start_time'  => $session['start_time'],
                'end_time'    => $session['end_time'],
                'classroom'   => $session['classroom'] ?? null,
            ]);
        }

        return $course->load('sessions');
    }

    public function updateCourse(int $userId, int $courseId, array $data): CourseModel
    {
        $course = CourseModel::query()
            ->where('user_id', $userId)
            ->where('id', $courseId)
            ->firstOrFail();

        $course->update([
            'name'           => $data['name'],
            'color'          => $data['color'] ?? 'primary',
            'starts_at'      => $data['starts_at'] ?? null,
            'ends_at'        => $data['ends_at'] ?? null,
            'professor'      => $data['professor'] ?? null,
            'credits'        => isset($data['credits']) && $data['credits'] !== '' ? (int) $data['credits'] : null,
            'target_grade'   => isset($data['target_grade']) && $data['target_grade'] !== '' ? (float) $data['target_grade'] : null,
            'min_pass_grade' => isset($data['min_pass_grade']) && $data['min_pass_grade'] !== '' ? (float) $data['min_pass_grade'] : null,
        ]);

        $course->sessions()->delete();

        foreach ($data['sessions'] as $session) {
            CourseSessionModel::query()->create([
                'course_id'   => $course->id,
                'day_of_week' => (int) $session['day_of_week'],
                'start_time'  => $session['start_time'],
                'end_time'    => $session['end_time'],
                'classroom'   => $session['classroom'] ?? null,
            ]);
        }

        return $course->load('sessions');
    }

    public function deleteCourse(int $userId, int $courseId): bool
    {
        return (bool) CourseModel::query()
            ->where('user_id', $userId)
            ->where('id', $courseId)
            ->delete();
    }

    // ── Apuntes ──────────────────────────────────────────────────────────────

    public function getNoteForCourse(int $userId, int $courseId): ?CourseNoteModel
    {
        return CourseNoteModel::query()
            ->where('user_id', $userId)
            ->where('course_id', $courseId)
            ->with('images')
            ->first();
    }

    /** @param array<string, mixed> $content */
    public function upsertNote(int $userId, int $courseId, array $content): CourseNoteModel
    {
        $note = CourseNoteModel::query()->firstOrNew([
            'user_id'   => $userId,
            'course_id' => $courseId,
        ]);

        $note->content = $content;
        $note->save();

        return $note->load('images');
    }
}
