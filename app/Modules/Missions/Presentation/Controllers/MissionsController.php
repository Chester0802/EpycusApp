<?php

declare(strict_types=1);

namespace App\Modules\Missions\Presentation\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Identity\Infrastructure\Models\UserModel;
use App\Modules\Missions\Application\DTOs\CreateMissionDTO;
use App\Modules\Missions\Application\DTOs\UpdateMissionDTO;
use App\Modules\Missions\Application\UseCases\AddSubtaskUseCase;
use App\Modules\Missions\Application\UseCases\ChangeQuadrantUseCase;
use App\Modules\Missions\Application\UseCases\CompleteMissionUseCase;
use App\Modules\Missions\Application\UseCases\CreateMissionUseCase;
use App\Modules\Missions\Application\UseCases\DeleteMissionUseCase;
use App\Modules\Missions\Application\UseCases\ReorderSubtasksUseCase;
use App\Modules\Missions\Application\UseCases\ToggleSubtaskUseCase;
use App\Modules\Missions\Application\UseCases\UncompleteMissionUseCase;
use App\Modules\Missions\Application\UseCases\UpdateMissionUseCase;
use App\Modules\Missions\Application\UseCases\UpdateSubtaskUseCase;
use App\Modules\Missions\Domain\Contracts\MissionRepositoryInterface;
use App\Modules\Missions\Infrastructure\Models\MissionModel;
use App\Modules\Missions\Infrastructure\Models\SubtaskModel;
use App\Modules\Pomodoro\Domain\Contracts\PomodoroRepositoryInterface;
use App\Modules\Pomodoro\Domain\ValueObjects\SessionState;
use App\Modules\Pomodoro\Infrastructure\Models\PomodoroSessionModel;
use App\Modules\Pomodoro\Infrastructure\Models\PomodoroSessionSubtaskModel;
use App\Shared\Domain\Contracts\UserProgressReaderInterface;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

final class MissionsController extends Controller
{
    public function __construct(
        private MissionRepositoryInterface $repository,
        private CreateMissionUseCase $createMission,
        private UpdateMissionUseCase $updateMission,
        private DeleteMissionUseCase $deleteMission,
        private CompleteMissionUseCase $completeMission,
        private UncompleteMissionUseCase $uncompleteMission,
        private ToggleSubtaskUseCase $toggleSubtask,
        private UpdateSubtaskUseCase $updateSubtask,
        private AddSubtaskUseCase $addSubtask,
        private ReorderSubtasksUseCase $reorderSubtasks,
        private ChangeQuadrantUseCase $changeQuadrant,
        private UserProgressReaderInterface $progress,
        private PomodoroRepositoryInterface $pomodoroRepo,
    ) {}

    public function index(Request $request): Response
    {
        $userId = (int) Auth::id();
        /** @var UserModel|null $user */
        $user = Auth::user();
        $sortBy = in_array($request->query('sort_by'), ['priority', 'difficulty', 'created_at'], true)
            ? (string) $request->query('sort_by') : 'default';

        $missions = $this->repository->getActiveForUser($userId, $sortBy);
        $completed = $this->repository->getCompletedForUser($userId);
        $today = Carbon::now()->toDateString();

        $this->markOverdue($missions, $today);

        $missionsData = $this->mapMissions($missions, $today);
        $completedData = $this->mapMissions($completed, $today);

        $courses = \Illuminate\Support\Facades\DB::table('courses')
            ->where('user_id', $userId)
            ->select('id', 'name', 'color')
            ->orderBy('name')
            ->get();

        return Inertia::render('Missions/Index', [
            'missions' => $missionsData,
            'completedMissions' => $completedData,
            'courses' => $courses,
            'todayDate' => $today,
            'sortBy' => $sortBy,
            'avatarStyle' => $user ? $user->avatar_style : 'base',
            'avatarGender' => $user ? $user->avatar_gender : 'm',
            'progress' => [
                'phase' => $this->progress->getPhaseFor($userId),
            ],
        ]);
    }

    /**
     * @param Collection<int, MissionModel> $missions
     */
    private function markOverdue(Collection $missions, string $today): void
    {
        foreach ($missions as $mission) {
            if ($mission->due_date && $mission->due_date->toDateString() < $today && ! $mission->is_overdue) {
                $mission->update(['is_overdue' => true]);
            }
        }
    }

    /**
     * @param Collection<int, MissionModel> $missions
     * @return array<int, array<string, mixed>>
     */
    private function mapMissions(Collection $missions, string $today): array
    {
        return $missions->map(fn (MissionModel $m) => $this->mapMission($m, $today))->values()->toArray();
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:160',
            'description' => 'nullable|string',
            'difficulty' => 'required|in:easy,medium,hard',
            'priority' => 'required|in:baja,normal,alta',
            'course_id' => 'nullable|integer|exists:courses,id',
            'eisenhower_quadrant' => 'nullable|in:q1,q2,q3,q4',
            'due_date' => 'nullable|date',
            'subtasks' => 'nullable|array',
            'subtasks.*' => 'string|max:160',
        ]);

        $dto = new CreateMissionDTO(
            userId: (int) Auth::id(),
            title: $validated['title'],
            description: $validated['description'] ?? null,
            difficulty: $validated['difficulty'],
            priority: $validated['priority'],
            dueDate: $validated['due_date'] ?? null,
            subtasks: $validated['subtasks'] ?? [],
            eisenhowerQuadrant: $validated['eisenhower_quadrant'] ?? 'q2',
            courseId: !empty($validated['course_id']) ? (int) $validated['course_id'] : null,
        );

        $this->createMission->execute($dto);

        return back()->with('success', 'Misión creada correctamente.');
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:160',
            'description' => 'nullable|string',
            'difficulty' => 'required|in:easy,medium,hard',
            'priority' => 'required|in:baja,normal,alta',
            'course_id' => 'nullable|integer|exists:courses,id',
            'eisenhower_quadrant' => 'nullable|in:q1,q2,q3,q4',
            'due_date' => 'nullable|date',
        ]);

        $dto = new UpdateMissionDTO(
            missionId: $id,
            userId: (int) Auth::id(),
            title: $validated['title'],
            description: $validated['description'] ?? null,
            difficulty: $validated['difficulty'],
            priority: $validated['priority'],
            dueDate: $validated['due_date'] ?? null,
            eisenhowerQuadrant: $validated['eisenhower_quadrant'] ?? null,
            courseId: !empty($validated['course_id']) ? (int) $validated['course_id'] : null,
        );

        $this->updateMission->execute($dto);

        return back()->with('success', 'Misión actualizada.');
    }

    public function show(int $id): Response
    {
        $userId = (int) Auth::id();
        $mission = $this->repository->findByIdAndUser($id, $userId);

        if (! $mission) {
            abort(404);
        }

        $today = Carbon::now()->toDateString();
        $data = $this->mapMission($mission, $today);

        /** @var Collection<int, PomodoroSessionModel> $rawPomodoros */
        $rawPomodoros = PomodoroSessionModel::query()
            ->where('mission_id', $id)
            ->where('status', SessionState::COMPLETED)
            ->with(['subtaskCompletions.subtask'])
            ->orderByDesc('started_at')
            ->get();

        $pomodoroSessions = $rawPomodoros->map(function (PomodoroSessionModel $s) {
            return [
                'id' => $s->id,
                'started_at' => $s->started_at->toIso8601String(),
                'focus_minutes' => $s->focus_minutes,
                'subtasks' => $s->subtaskCompletions->map(function ($p) {
                    /** @var PomodoroSessionSubtaskModel $p */
                    return [
                        'id' => $p->subtask_id,
                        'title' => $p->subtask ? $p->subtask->title : '',
                        'completed_at' => $p->completed_at->toIso8601String(),
                    ];
                })->values()->toArray(),
            ];
        })->values()->toArray();

        $courses = \Illuminate\Support\Facades\DB::table('courses')
            ->where('user_id', $userId)
            ->select('id', 'name', 'color')
            ->orderBy('name')
            ->get();

        return Inertia::render('Missions/Detail', [
            'mission' => $data,
            'courses' => $courses,
            'pomodoroSessions' => $pomodoroSessions,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function mapMission(MissionModel $m, string $today): array
    {
        $subtasks = $m->subtasks->map(fn (SubtaskModel $s) => [
            'id' => $s->id,
            'title' => $s->title,
            'is_completed' => $s->is_completed,
            'sort_order' => $s->sort_order,
        ])->values()->toArray();

        $allDone = $m->subtasks->count() > 0 && $m->subtasks->every(fn (SubtaskModel $s) => $s->is_completed);

        return [
            'id' => $m->id,
            'course_id' => $m->course_id,
            'course' => $m->course ? [
                'id' => $m->course->id,
                'name' => $m->course->name,
                'color' => $m->course->color,
            ] : null,
            'title' => $m->title,
            'description' => $m->description,
            'difficulty' => $m->difficulty,
            'priority' => $m->priority,
            'eisenhower_quadrant' => $m->eisenhower_quadrant ?? 'q2',
            'due_date' => $m->due_date?->toDateString(),
            'is_overdue' => $m->is_overdue,
            'is_completed' => (bool) $m->completed_at,
            'completed_at' => $m->completed_at?->toDateString(),
            'days_early_or_late' => $m->days_early_or_late,
            'xp_awarded' => $m->xp_awarded,
            'state' => $m->completed_at ? 'completed' : ($m->is_overdue ? 'overdue' : ($m->subtasks->where('is_completed', true)->count() > 0 ? 'in_progress' : 'pending')),
            'subtasks' => $subtasks,
            'subtask_count' => $m->subtasks->count(),
            'subtask_done' => $m->subtasks->where('is_completed', true)->count(),
        ];
    }

    public function changeQuadrant(Request $request, int $id): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'quadrant' => 'required|in:q1,q2,q3,q4',
        ]);

        try {
            $this->changeQuadrant->execute($id, (int) Auth::id(), $validated['quadrant']);

            if ($request->wantsJson()) {
                return response()->json(['success' => true, 'quadrant' => $validated['quadrant']]);
            }

            return back()->with('success', 'Cuadrante actualizado.');
        } catch (\RuntimeException $e) {
            if ($request->wantsJson()) {
                return response()->json(['error' => $e->getMessage()], 400);
            }

            return back()->with('error', $e->getMessage());
        }
    }

    public function complete(int $id): RedirectResponse
    {
        $this->completeMission->execute($id, (int) Auth::id());

        $xp = session()->pull('xp_awarded', 0);
        $msg = $xp > 0 ? "Misión completada. ¡+{$xp} XP!" : 'Misión completada.';

        return back()->with('success', $msg);
    }

    public function uncomplete(int $id): RedirectResponse
    {
        $this->uncompleteMission->execute($id, (int) Auth::id());

        return back()->with('success', 'Misión reactivada.');
    }

    public function toggleSubtask(int $id, int $subtaskId): JsonResponse|RedirectResponse
    {
        try {
            $result = $this->toggleSubtask->execute($subtaskId, (int) Auth::id());

            if ($result['completed']) {
                $this->recordSubtaskInActiveSession($subtaskId, (int) Auth::id());
            }

            if (request()->wantsJson()) {
                return response()->json($result);
            }

            return back();
        } catch (\RuntimeException $e) {
            return back()->with('error', 'No puedes modificar esta subtarea.');
        }
    }

    private function recordSubtaskInActiveSession(int $subtaskId, int $userId): void
    {
        $session = $this->pomodoroRepo->findActiveForUser($userId);
        if ($session && $session->mission_id) {
            PomodoroSessionSubtaskModel::firstOrCreate([
                'pomodoro_session_id' => $session->id,
                'subtask_id' => $subtaskId,
            ], [
                'completed_at' => Carbon::now(),
            ]);
        }
    }

    public function updateSubtask(Request $request, int $id, int $subtaskId): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:160',
        ]);

        try {
            $this->updateSubtask->execute($subtaskId, (int) Auth::id(), $validated['title']);

            return back();
        } catch (\RuntimeException $e) {
            return back()->with('error', 'No puedes modificar esta subtarea.');
        }
    }

    public function addSubtask(Request $request, int $id): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:160',
        ]);

        try {
            $this->addSubtask->execute($id, (int) Auth::id(), $validated['title']);

            return back();
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function reorderSubtasks(Request $request, int $id): RedirectResponse
    {
        $validated = $request->validate([
            'ordered_ids' => 'required|array',
            'ordered_ids.*' => 'integer|exists:subtasks,id',
        ]);

        try {
            $this->reorderSubtasks->execute($id, (int) Auth::id(), $validated['ordered_ids']);

            return back();
        } catch (\RuntimeException $e) {
            return back()->with('error', 'No puedes reordenar estas subtareas.');
        }
    }

    public function destroy(int $id): RedirectResponse
    {
        $this->deleteMission->execute($id, (int) Auth::id());

        return back()->with('success', 'Misión eliminada.');
    }
}
