<?php

declare(strict_types=1);

namespace App\Modules\Calendar\Domain\Contracts;

use App\Modules\Calendar\Infrastructure\Models\CourseModel;
use App\Modules\Calendar\Infrastructure\Models\CourseNoteModel;
use App\Modules\Calendar\Infrastructure\Models\HolidayModel;
use Illuminate\Support\Collection;

interface CalendarRepositoryInterface
{
    /** @return Collection<int, HolidayModel> */
    public function getHolidaysInMonth(int $year, int $month): Collection;

    // ── Cursos (reemplaza class_schedules) ──────────────────────────────────

    /** @return Collection<int, CourseModel> con sessions cargadas */
    public function getCoursesForUser(int $userId): Collection;

    /** @param array<string, mixed> $data */
    public function createCourse(int $userId, array $data): CourseModel;

    /** @param array<string, mixed> $data */
    public function updateCourse(int $userId, int $courseId, array $data): CourseModel;

    public function deleteCourse(int $userId, int $courseId): bool;

    // ── Apuntes ─────────────────────────────────────────────────────────────

    public function getNoteForCourse(int $userId, int $courseId): ?CourseNoteModel;

    /** @param array<string, mixed> $content */
    public function upsertNote(int $userId, int $courseId, array $content): CourseNoteModel;
}
