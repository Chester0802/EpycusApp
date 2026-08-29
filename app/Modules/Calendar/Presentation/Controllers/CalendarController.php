<?php

declare(strict_types=1);

namespace App\Modules\Calendar\Presentation\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Calendar\Domain\Contracts\CalendarRepositoryInterface;
use App\Modules\DayPlanner\Application\UseCases\CreateDayPlanItemUseCase;
use App\Modules\DayPlanner\Application\UseCases\DeleteDayPlanItemUseCase;
use App\Modules\DayPlanner\Application\UseCases\GetOrGenerateDailyPlanUseCase;
use App\Modules\DayPlanner\Application\UseCases\SaveDailyRoutinesUseCase;
use App\Modules\DayPlanner\Application\UseCases\UpdateDayPlanItemStatusUseCase;
use App\Modules\DayPlanner\Application\UseCases\UpdateDayPlanItemUseCase;
use App\Modules\Missions\Domain\Contracts\MissionRepositoryInterface;
use App\Shared\Domain\Contracts\CalendarReaderInterface;
use App\Shared\Domain\Contracts\UserProgressReaderInterface;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

final class CalendarController extends Controller
{
    public function __construct(
        private readonly CalendarRepositoryInterface $calendar,
        private readonly CalendarReaderInterface $calendarReader,
        private readonly MissionRepositoryInterface $missions,
        private readonly GetOrGenerateDailyPlanUseCase $getPlan,
        private readonly UpdateDayPlanItemStatusUseCase $updateStatus,
        private readonly CreateDayPlanItemUseCase $createItem,
        private readonly UpdateDayPlanItemUseCase $updateItem,
        private readonly DeleteDayPlanItemUseCase $deleteItem,
        private readonly SaveDailyRoutinesUseCase $routinesUseCase,
        private readonly UserProgressReaderInterface $progress,
    ) {}

    public function index(Request $request): Response
    {
        $userId = (int) Auth::id();
        /** @var \App\Modules\Identity\Infrastructure\Models\UserModel|null $user */
        $user   = Auth::user();
        $month  = $request->integer('month', Carbon::now('America/Lima')->month);
        $year   = $request->integer('year', Carbon::now('America/Lima')->year);
        $selectedDate = $request->query('date', Carbon::now('America/Lima')->toDateString());

        $start = Carbon::createFromDate($year, $month, 1, 'America/Lima')->startOfMonth();
        $end   = (clone $start)->endOfMonth();
        $today = Carbon::now('America/Lima')->toDateString();

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

        // Cargar cursos con sus sesiones
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

        // Cargar plan diario integrado
        $plan = $this->getPlan->execute($userId, is_string($selectedDate) ? $selectedDate : null);

        return Inertia::render('Calendar/Index', [
            'month'          => $month,
            'year'           => $year,
            'todayDate'      => $today,
            'selectedDate'   => $selectedDate,
            'holidays'       => $holidays,
            'missionsByDate' => $missionsByDate,
            'examDates'      => $examDates,
            'courses'        => $courses,
            'plan'           => $plan,
            'academicCycle'  => config('academic.current_cycle.name', '2026-2'),
            'avatarStyle'    => $user?->avatar_style ?? 'base',
            'avatarGender'   => $user?->avatar_gender ?? 'm',
            'progress'       => [
                'total_xp' => $this->progress->getTotalXpFor($userId),
                'phase'    => $this->progress->getPhaseFor($userId),
                'streak'   => $this->progress->getCurrentStreakFor($userId),
            ],
            'xp_awarded'     => session()->pull('xp_awarded', 0),
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

    // ── Day Planner / Time-Blocking Integrado ──────────────────────────────────

    public function updatePlanItemStatus(Request $request, int $id): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,done,skipped,postponed',
            'skip_reason' => 'nullable|string|max:80',
            'postpone_to_block' => 'nullable|in:morning,afternoon,night,anytime',
        ]);

        $userId = (int) Auth::id();

        try {
            $result = $this->updateStatus->execute(
                itemId: $id,
                userId: $userId,
                status: $validated['status'],
                skipReason: $validated['skip_reason'] ?? null,
                postponeToBlock: $validated['postpone_to_block'] ?? null,
            );

            if ($request->wantsJson() && ! $request->header('X-Inertia')) {
                return response()->json($result);
            }

            return back()->with([
                'success' => $this->getStatusMessage($validated['status']),
                'xp_awarded' => $result['xp_awarded'],
            ]);
        } catch (Exception $e) {
            if ($request->wantsJson() && ! $request->header('X-Inertia')) {
                return response()->json(['error' => $e->getMessage()], 422);
            }

            return back()->with('error', $e->getMessage());
        }
    }

    public function storePlanItem(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'plan_date' => 'required|date',
            'title' => 'required|string|max:160',
            'category' => 'required|string|max:40',
            'time_block' => 'required|in:morning,afternoon,night,anytime',
            'scheduled_time' => 'nullable|string|max:10',
            'estimated_minutes' => 'nullable|integer|min:1|max:480',
            'notes' => 'nullable|string|max:500',
        ]);

        $userId = (int) Auth::id();

        $this->createItem->execute($userId, $validated);

        return back()->with('success', 'Actividad añadida al plan del día.');
    }

    public function updatePlanItem(Request $request, int $id): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:160',
            'category' => 'sometimes|required|string|max:40',
            'time_block' => 'sometimes|required|in:morning,afternoon,night,anytime',
            'scheduled_time' => 'nullable|string|max:10',
            'estimated_minutes' => 'nullable|integer|min:1|max:480',
            'notes' => 'nullable|string|max:500',
        ]);

        $userId = (int) Auth::id();

        try {
            $this->updateItem->execute($id, $userId, $validated);

            return back()->with('success', 'Actividad actualizada.');
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function destroyPlanItem(int $id): RedirectResponse
    {
        $userId = (int) Auth::id();

        try {
            $this->deleteItem->execute($id, $userId);

            return back()->with('success', 'Actividad eliminada.');
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function storeRoutine(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:160',
            'category' => 'required|string|max:40',
            'time_block' => 'required|in:morning,afternoon,night,anytime',
            'scheduled_time' => 'nullable|string|max:10',
            'estimated_minutes' => 'nullable|integer|min:1|max:480',
            'days_of_week' => 'nullable|array',
        ]);

        $userId = (int) Auth::id();

        $this->routinesUseCase->create($userId, $validated);

        return back()->with('success', 'Plantilla de rutina guardada.');
    }

    public function updateRoutine(Request $request, int $id): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:160',
            'category' => 'sometimes|required|string|max:40',
            'time_block' => 'sometimes|required|in:morning,afternoon,night,anytime',
            'scheduled_time' => 'nullable|string|max:10',
            'estimated_minutes' => 'nullable|integer|min:1|max:480',
            'days_of_week' => 'nullable|array',
            'is_active' => 'sometimes|boolean',
        ]);

        $userId = (int) Auth::id();

        $this->routinesUseCase->update($id, $userId, $validated);

        return back()->with('success', 'Plantilla de rutina actualizada.');
    }

    public function destroyRoutine(int $id): RedirectResponse
    {
        $userId = (int) Auth::id();

        $this->routinesUseCase->delete($id, $userId);

        return back()->with('success', 'Plantilla de rutina eliminada.');
    }

    public function applyRoutines(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'plan_date' => 'required|date',
        ]);

        $userId = (int) Auth::id();
        $this->getPlan->applyRoutinesToDate($userId, $validated['plan_date']);

        return back()->with('success', 'Plantillas de rutina aplicadas al plan del día.');
    }

    public function loadStarterTemplate(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'plan_date' => 'required|date',
        ]);

        $userId = (int) Auth::id();
        $this->getPlan->seedStarterTemplate($userId, $validated['plan_date']);

        return back()->with('success', 'Plantilla recomendada cargada con éxito.');
    }

    private function getStatusMessage(string $status): string
    {
        return match ($status) {
            'done' => '¡Excelente! Actividad marcada como completada.',
            'skipped' => 'Actividad saltada registrada.',
            'postponed' => 'Actividad postergada al siguiente bloque.',
            default => 'Estado actualizado.',
        };
    }
}
