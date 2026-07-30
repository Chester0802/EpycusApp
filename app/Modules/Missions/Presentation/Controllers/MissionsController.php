<?php

declare(strict_types=1);

namespace App\Modules\Missions\Presentation\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Missions\Application\DTOs\CreateMissionDTO;
use App\Modules\Missions\Application\DTOs\UpdateMissionDTO;
use App\Modules\Missions\Application\UseCases\AddSubtaskUseCase;
use App\Modules\Missions\Application\UseCases\CompleteMissionUseCase;
use App\Modules\Missions\Application\UseCases\CreateMissionUseCase;
use App\Modules\Missions\Application\UseCases\DeleteMissionUseCase;
use App\Modules\Missions\Application\UseCases\ReorderSubtasksUseCase;
use App\Modules\Missions\Application\UseCases\ToggleSubtaskUseCase;
use App\Modules\Missions\Application\UseCases\UpdateMissionUseCase;
use App\Modules\Missions\Application\UseCases\UpdateSubtaskUseCase;
use App\Modules\Missions\Domain\Contracts\MissionRepositoryInterface;
use App\Modules\Pomodoro\Domain\Contracts\PomodoroRepositoryInterface;
use App\Modules\Pomodoro\Domain\ValueObjects\SessionState;
use App\Modules\Pomodoro\Infrastructure\Models\PomodoroSessionModel;
use App\Modules\Pomodoro\Infrastructure\Models\PomodoroSessionSubtaskModel;
use App\Shared\Domain\Contracts\UserProgressReaderInterface;
use App\Shared\Domain\Services\AvatarAssetResolver;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
        private ToggleSubtaskUseCase $toggleSubtask,
        private UpdateSubtaskUseCase $updateSubtask,
        private AddSubtaskUseCase $addSubtask,
        private ReorderSubtasksUseCase $reorderSubtasks,
        private UserProgressReaderInterface $progress,
        private PomodoroRepositoryInterface $pomodoroRepo,
        private AvatarAssetResolver $avatarResolver,
    ) {}

    public function index(Request $request): Response
    {
        $userId = (int) Auth::id();
        $user = Auth::user();
        $sortBy = in_array($request->query('sort_by'), ['priority', 'difficulty', 'created_at'], true)
            ? $request->query('sort_by') : 'default';

        $missions = $this->repository->getActiveForUser($userId, $sortBy);
        $completed = $this->repository->getCompletedForUser($userId);
        $today = Carbon::now()->toDateString();

        $this->markOverdue($missions, $today);

        $missionsData = $this->mapMissions($missions, $today);
        $completedData = $this->mapMissions($completed, $today);
        $avatarImage = $this->avatarResolver->imageForModule(
            $user?->avatar_style,
            $user?->avatar_gender,
            'missions'
        );

        return Inertia::render('Missions/Index', [
            'missions' => $missionsData,
            'completedMissions' => $completedData,
            'todayDate' => $today,
            'sortBy' => $sortBy,
            'avatarImage' => $avatarImage,
        ]);
    }

    private function markOverdue($missions, string $today): void
    {
        foreach ($missions as $mission) {
            if ($mission->due_date && $mission->due_date < $today && ! $mission->is_overdue) {
                $mission->update(['is_overdue' => true]);
            }
        }
    }

    private function mapMissions($missions, string $today): array
    {
        return $missions->map(fn ($m) => $this->mapMission($m, $today))->toArray();
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:160',
            'description' => 'nullable|string',
            'difficulty' => 'required|in:easy,medium,hard',
            'priority' => 'required|in:baja,normal,alta',
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

        $pomodoroSessions = PomodoroSessionModel::with(['subtaskCompletions.subtask'])
            ->where('mission_id', $id)
            ->where('status', SessionState::COMPLETED)
            ->orderByDesc('started_at')
            ->get()
            ->map(fn ($s) => [
                'id' => $s->id,
                'started_at' => $s->started_at->toIso8601String(),
                'focus_minutes' => $s->focus_minutes,
                'subtasks' => $s->subtaskCompletions->map(fn ($p) => [
                    'id' => $p->subtask_id,
                    'title' => $p->subtask->title,
                    'completed_at' => $p->completed_at->toIso8601String(),
                ]),
            ]);

        return Inertia::render('Missions/Detail', [
            'mission' => $data,
            'pomodoroSessions' => $pomodoroSessions,
        ]);
    }

    private function mapMission($m, string $today): array
    {
        $subtasks = $m->subtasks->map(fn ($s) => [
            'id' => $s->id,
            'title' => $s->title,
            'is_completed' => $s->is_completed,
            'sort_order' => $s->sort_order,
        ]);

        $allDone = $m->subtasks->count() > 0 && $m->subtasks->every(fn ($s) => $s->is_completed);

        return [
            'id' => $m->id,
            'title' => $m->title,
            'description' => $m->description,
            'difficulty' => $m->difficulty,
            'priority' => $m->priority,
            'due_date' => $m->due_date?->toDateString(),
            'is_overdue' => $m->is_overdue,
            'is_completed' => (bool) $m->completed_at,
            'completed_at' => $m->completed_at?->toDateString(),
            'days_early_or_late' => $m->days_early_or_late,
            'xp_awarded' => $m->xp_awarded,
            'state' => $m->completed_at ? 'completed' : ($m->is_overdue ? 'overdue' : ($allDone ? 'completed' : ($m->subtasks->where('is_completed', true)->count() > 0 ? 'in_progress' : 'pending'))),
            'subtasks' => $subtasks,
            'subtask_count' => $m->subtasks->count(),
            'subtask_done' => $m->subtasks->where('is_completed', true)->count(),
        ];
    }

    public function complete(int $id): RedirectResponse
    {
        $this->completeMission->execute($id, (int) Auth::id());

        $xp = session()->pull('xp_awarded', 0);
        $msg = $xp > 0 ? "Misión completada. ¡+{$xp} XP!" : 'Misión completada.';

        return back()->with('success', $msg);
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
