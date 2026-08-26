<?php

declare(strict_types=1);

namespace App\Modules\Calendar\Presentation\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Calendar\Domain\Contracts\CalendarRepositoryInterface;
use App\Modules\Missions\Domain\Contracts\MissionRepositoryInterface;
use App\Shared\Domain\Contracts\CalendarReaderInterface;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

final class CalendarController extends Controller
{
    public function __construct(
        private CalendarRepositoryInterface $calendar,
        private CalendarReaderInterface $calendarReader,
        private MissionRepositoryInterface $missions,
    ) {}

    public function index(Request $request): Response
    {
        $userId = (int) Auth::id();
        $month  = $request->integer('month', Carbon::now()->month);
        $year   = $request->integer('year', Carbon::now()->year);

        $start = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $end   = (clone $start)->endOfMonth();
        $today = Carbon::now()->toDateString();

        $holidays = $this->calendar->getHolidaysInMonth($year, $month)
            ->keyBy(fn ($h) => $h->date->toDateString())
            ->map(fn ($h) => ['name' => $h->name, 'type' => $h->type])
            ->toArray();

        $activeMissions    = $this->missions->getActiveForUser($userId);
        $completedMissions = $this->missions->getCompletedForUser($userId);
        $allMissions       = $activeMissions->merge($completedMissions);

        $missionsByDate = [];
        foreach ($allMissions as $m) {
            $date = $m->due_date?->toDateString();
            if ($date && $date >= $start->toDateString() && $date <= $end->toDateString()) {
                $missionsByDate[$date][] = [
                    'id'           => $m->id,
                    'title'        => $m->title,
                    'difficulty'   => $m->difficulty,
                    'is_completed' => (bool) $m->completed_at,
                ];
            }
        }

        $examDates = [];

        // Cargar cursos con sus sesiones (reemplaza class_schedules)
        $courses = $this->calendar->getCoursesForUser($userId)
            ->map(fn ($c) => [
                'id'        => $c->id,
                'name'      => $c->name,
                'color'     => $c->color,
                'starts_at' => $c->starts_at?->toDateString(),
                'ends_at'   => $c->ends_at?->toDateString(),
                'sessions'  => $c->sessions->map(fn ($s) => [
                    'id'          => $s->id,
                    'day_of_week' => $s->day_of_week,
                    'start_time'  => substr((string) $s->start_time, 0, 5),
                    'end_time'    => substr((string) $s->end_time, 0, 5),
                    'classroom'   => $s->classroom,
                ])->values()->toArray(),
            ])
            ->toArray();

        return Inertia::render('Calendar/Index', [
            'month'         => $month,
            'year'          => $year,
            'todayDate'     => $today,
            'holidays'      => $holidays,
            'missionsByDate'=> $missionsByDate,
            'examDates'     => $examDates,
            'courses'       => $courses,
            'academicCycle' => config('academic.current_cycle.name', '2026-2'),
        ]);
    }

    // ── Cursos ────────────────────────────────────────────────────────────────

    public function storeCourse(Request $request): RedirectResponse
    {
        $userId = (int) Auth::id();

        $validated = $request->validate([
            'name'                       => ['required', 'string', 'max:120'],
            'color'                      => ['nullable', 'string', 'in:primary,accent,success,warning,secondary'],
            'starts_at'                  => ['nullable', 'date'],
            'ends_at'                    => ['nullable', 'date', 'after_or_equal:starts_at'],
            'sessions'                   => ['required', 'array', 'min:1', 'max:7'],
            'sessions.*.day_of_week'     => ['required', 'integer', 'between:1,7'],
            'sessions.*.start_time'      => ['required', 'date_format:H:i'],
            'sessions.*.end_time'        => ['required', 'date_format:H:i', 'after:sessions.*.start_time'],
            'sessions.*.classroom'       => ['nullable', 'string', 'max:60'],
        ]);

        $this->calendar->createCourse($userId, $validated);

        return back()->with('success', 'Curso registrado correctamente.');
    }

    public function updateCourse(Request $request, int $id): RedirectResponse
    {
        $userId = (int) Auth::id();

        $validated = $request->validate([
            'name'                       => ['required', 'string', 'max:120'],
            'color'                      => ['nullable', 'string', 'in:primary,accent,success,warning,secondary'],
            'starts_at'                  => ['nullable', 'date'],
            'ends_at'                    => ['nullable', 'date', 'after_or_equal:starts_at'],
            'sessions'                   => ['required', 'array', 'min:1', 'max:7'],
            'sessions.*.day_of_week'     => ['required', 'integer', 'between:1,7'],
            'sessions.*.start_time'      => ['required', 'date_format:H:i'],
            'sessions.*.end_time'        => ['required', 'date_format:H:i', 'after:sessions.*.start_time'],
            'sessions.*.classroom'       => ['nullable', 'string', 'max:60'],
        ]);

        $this->calendar->updateCourse($userId, $id, $validated);

        return back()->with('success', 'Curso actualizado correctamente.');
    }

    public function destroyCourse(int $id): RedirectResponse
    {
        $userId = (int) Auth::id();

        $this->calendar->deleteCourse($userId, $id);

        return back()->with('success', 'Curso eliminado.');
    }

    // ── Apuntes ───────────────────────────────────────────────────────────────

    public function showNote(int $courseId): JsonResponse
    {
        $userId = (int) Auth::id();

        // Verificar que el curso pertenece al usuario
        $note = $this->calendar->getNoteForCourse($userId, $courseId);

        return response()->json([
            'note' => $note ? [
                'id'         => $note->id,
                'content'    => $note->content,
                'updated_at' => $note->updated_at?->toIso8601String(),
                'images'     => $note->images->map(fn ($img) => [
                    'id'            => $img->id,
                    'original_name' => $img->original_name,
                    'url'           => route('note-images.show', ['id' => $img->id]),
                    'mime_type'     => $img->mime_type,
                    'size'          => $img->size,
                ])->values()->toArray(),
            ] : null,
        ]);
    }

    public function upsertNote(Request $request, int $courseId): JsonResponse
    {
        $userId = (int) Auth::id();

        $validated = $request->validate([
            'content'                        => ['required', 'array'],
            'content.version'                => ['required', 'string'],
            'content.entries'                => ['present', 'array'],
            'content.entries.*.id'           => ['required', 'string'],
            'content.entries.*.recorded_at'  => ['required', 'string'],
            'content.entries.*.blocks'       => ['present', 'array'],
        ]);

        // Verificar que el curso pertenece al usuario antes de guardar
        $courses = $this->calendar->getCoursesForUser($userId);
        $course  = $courses->firstWhere('id', $courseId);

        if (! $course) {
            return response()->json(['error' => 'Curso no encontrado.'], 403);
        }

        $note = $this->calendar->upsertNote($userId, $courseId, $validated['content']);

        return response()->json([
            'note' => [
                'id'         => $note->id,
                'content'    => $note->content,
                'updated_at' => $note->updated_at?->toIso8601String(),
                'images'     => $note->images->map(fn ($img) => [
                    'id'            => $img->id,
                    'original_name' => $img->original_name,
                    'url'           => route('note-images.show', ['id' => $img->id]),
                    'mime_type'     => $img->mime_type,
                    'size'          => $img->size,
                ])->values()->toArray(),
            ],
        ]);
    }
}
