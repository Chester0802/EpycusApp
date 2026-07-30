<?php

declare(strict_types=1);

namespace App\Modules\Pomodoro\Presentation\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Missions\Domain\Contracts\MissionRepositoryInterface;
use App\Modules\Pomodoro\Application\UseCases\AbandonPomodoroUseCase;
use App\Modules\Pomodoro\Application\UseCases\CompletePomodoroUseCase;
use App\Modules\Pomodoro\Application\UseCases\GetActiveSessionUseCase;
use App\Modules\Pomodoro\Application\UseCases\PausePomodoroUseCase;
use App\Modules\Pomodoro\Application\UseCases\ResumePomodoroUseCase;
use App\Modules\Pomodoro\Application\UseCases\StartPomodoroUseCase;
use App\Modules\Pomodoro\Domain\Contracts\PomodoroRepositoryInterface;
use App\Modules\Pomodoro\Domain\ValueObjects\SessionState;
use App\Modules\Pomodoro\Infrastructure\Models\PomodoroSessionModel;
use App\Shared\Domain\Contracts\UserProgressReaderInterface;
use App\Shared\Domain\Services\AvatarAssetResolver;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

final class PomodoroController extends Controller
{
    public function __construct(
        private PomodoroRepositoryInterface $repository,
        private GetActiveSessionUseCase $getActiveSession,
        private StartPomodoroUseCase $startPomodoro,
        private PausePomodoroUseCase $pausePomodoro,
        private ResumePomodoroUseCase $resumePomodoro,
        private CompletePomodoroUseCase $completePomodoro,
        private AbandonPomodoroUseCase $abandonPomodoro,
        private AvatarAssetResolver $avatars,
        private UserProgressReaderInterface $progress,
        private MissionRepositoryInterface $missions,
    ) {}

    public function index(): Response
    {
        $userId = (int) Auth::id();
        $user = Auth::user();

        // Esto es lo que resuelve "cerré el navegador con un Pomodoro
        // corriendo": cada vez que se visita esta página, se comprueba si
        // la sesión que quedó `running` ya debería haber terminado, y si
        // sí, se completa sola antes de mostrar nada (ver
        // ResolveStaleSessionUseCase).
        $xpBefore = $this->progress->getTotalXpFor($userId);
        $resolved = $this->getActiveSession->execute($userId);
        $xpFromAutoComplete = max(0, $this->progress->getTotalXpFor($userId) - $xpBefore);

        $today = $this->repository->todaysSessionsForUser($userId);
        $lastWeek = $this->repository->sessionsSinceForUser($userId, CarbonImmutable::now()->subDays(7));

        $completed = $lastWeek->where('status', SessionState::COMPLETED);
        $startedCount = $lastWeek->count();

        $todayDate = Carbon::now()->toDateString();
        $missions = $this->missions->getActiveForUser($userId);
        $missionsData = $missions->map(fn ($m) => [
            'id' => $m->id,
            'title' => $m->title,
            'difficulty' => $m->difficulty,
            'priority' => $m->priority,
            'due_date' => $m->due_date?->toDateString(),
            'is_overdue' => $m->is_overdue,
            'subtask_count' => $m->subtasks->count(),
            'subtask_done' => $m->subtasks->where('is_completed', true)->count(),
            'subtasks' => $m->subtasks->map(fn ($s) => [
                'id' => $s->id,
                'title' => $s->title,
                'is_completed' => $s->is_completed,
            ])->values(),
        ])->values();

        return Inertia::render('Pomodoro/Index', [
            'activeSession' => $resolved->session ? $this->serializeSession($resolved->session) : null,
            'autoCompletedFocusMinutes' => $resolved->autoCompletedFocusMinutes,
            'autoCompletedXp' => $resolved->autoCompletedFocusMinutes !== null ? $xpFromAutoComplete : null,
            'todaySessions' => $today->map(fn ($s) => $this->serializeSession($s))->values(),
            'stats' => [
                'sessionsCompleted' => $completed->count(),
                'sessionsStarted' => $startedCount,
                'completionRate' => $startedCount > 0 ? round(($completed->count() / $startedCount) * 100) : 0,
                'focusMinutesTotal' => (int) $completed->sum('focus_minutes'),
            ],
            // Personaje fijo de Pomodoro (orden=4) — "sentado estudiando".
            'avatarImage' => $this->avatars->imageForModule($user?->avatar_style, $user?->avatar_gender, 'pomodoro'),
            // Misiones activas para el panel lateral
            'missions' => $missionsData,
        ]);
    }

    public function start(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'planned_minutes' => 'required|integer|min:15|max:50',
            'mission_id' => 'nullable|integer',
        ]);

        $session = $this->startPomodoro->execute(
            userId: (int) Auth::id(),
            plannedMinutes: (int) $validated['planned_minutes'],
            missionId: isset($validated['mission_id']) ? (int) $validated['mission_id'] : null,
        );

        return response()->json($this->serializeSession($session));
    }

    public function pause(int $id): JsonResponse
    {
        $session = $this->pausePomodoro->execute($id, (int) Auth::id());

        return response()->json($this->serializeSession($session));
    }

    public function resume(int $id): JsonResponse
    {
        $session = $this->resumePomodoro->execute($id, (int) Auth::id());

        return response()->json($this->serializeSession($session));
    }

    public function complete(int $id): JsonResponse
    {
        $userId = (int) Auth::id();

        // Mismo truco que HabitsController::toggle() — diferencia de
        // totales en vez de un quinto método ad-hoc en el reader.
        $xpBefore = $this->progress->getTotalXpFor($userId);
        $session = $this->completePomodoro->execute($id, $userId);
        $xpAfter = $this->progress->getTotalXpFor($userId);

        return response()->json([
            ...$this->serializeSession($session),
            'xp_awarded' => max(0, $xpAfter - $xpBefore),
        ]);
    }

    public function abandon(int $id): JsonResponse
    {
        $session = $this->abandonPomodoro->execute($id, (int) Auth::id());

        return response()->json($this->serializeSession($session));
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeSession(PomodoroSessionModel $session): array
    {
        return [
            'id' => $session->id,
            'planned_minutes' => $session->planned_minutes,
            'started_at' => $session->started_at->toIso8601String(),
            'paused_at' => $session->paused_at?->toIso8601String(),
            'total_paused_seconds' => $session->total_paused_seconds,
            'status' => $session->status,
            'focus_minutes' => $session->focus_minutes,
            // El cliente reconstruye el conteo regresivo con esto — no
            // confía en su propio reloj/JS previo, siempre parte de acá
            // (ver Pomodoro/Index.vue).
            'server_now' => CarbonImmutable::now()->toIso8601String(),
        ];
    }
}
