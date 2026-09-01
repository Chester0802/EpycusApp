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
        private UserProgressReaderInterface $progress,
        private MissionRepositoryInterface $missions,
    ) {}

    public function index(): Response
    {
        $userId = (int) Auth::id();
        /** @var \App\Modules\Identity\Infrastructure\Models\UserModel|null $user */
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
        $lastWeek = $this->repository->sessionsSinceForUser($userId, CarbonImmutable::now('America/Lima')->subDays(7));

        $completed = $lastWeek->where('status', SessionState::COMPLETED);
        $startedCount = $lastWeek->count();

        $missions = $this->missions->getActiveForUser($userId);
        $missionsData = $missions->map(function ($m) {
            /** @var \App\Modules\Missions\Infrastructure\Models\MissionModel $m */
            return [
                'id' => $m->id,
                'title' => $m->title,
                'mission_type' => $m->mission_type ?? 'academic',
                'difficulty' => $m->difficulty,
                'priority' => $m->priority,
                'eisenhower_quadrant' => $m->eisenhower_quadrant ?? 'q2',
                'due_date' => $m->due_date?->toDateString(),
                'is_overdue' => $m->is_overdue,
                'subtask_count' => $m->subtasks->count(),
                'subtask_done' => $m->subtasks->where('is_completed', true)->count(),
                'subtasks' => $m->subtasks->map(function ($s) {
                    /** @var \App\Modules\Missions\Infrastructure\Models\SubtaskModel $s */
                    return [
                        'id' => $s->id,
                        'title' => $s->title,
                        'is_completed' => (bool) $s->is_completed,
                    ];
                })->values()->all(),
            ];
        })->values()->all();

        $courses = \App\Modules\Calendar\Infrastructure\Models\CourseModel::where('user_id', $userId)
            ->get()
            ->map(fn ($c) => [
                'id' => $c->id,
                'name' => $c->name,
                'color' => $c->color ?? '#3b82f6',
                'professor' => $c->professor,
                'credits' => $c->credits,
            ]);

        $readings = \App\Modules\Readings\Infrastructure\Models\ReadingModel::forUser($userId)
            ->whereIn('status', ['reading', 'want_to_read'])
            ->get()
            ->map(fn ($r) => [
                'id' => $r->id,
                'title' => $r->title,
                'author' => $r->author,
                'current_page' => $r->current_page,
                'total_pages' => $r->total_pages,
                'status' => $r->status,
            ]);

        $skills = \App\Modules\Skills\Infrastructure\Models\SkillModel::forUser($userId)
            ->get()
            ->map(fn ($s) => [
                'id' => $s->id,
                'name' => $s->name,
                'category' => $s->category,
                'current_level' => $s->current_level,
                'current_xp' => $s->current_xp,
                'target_xp' => $s->target_xp,
            ]);

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
            'avatarStyle' => $user ? $user->avatar_style : 'base',
            'avatarGender' => $user ? $user->avatar_gender : 'm',
            'progress' => [
                'phase' => $this->progress->getPhaseFor($userId),
            ],
            'missions' => $missionsData,
            'courses' => $courses,
            'readings' => $readings,
            'skills' => $skills,
        ]);
    }

    public function start(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'planned_minutes' => 'required|integer|min:15|max:50',
            'mission_id' => 'nullable|integer',
            'study_group_session_id' => 'nullable|integer',
            'context_type' => 'nullable|string|max:30',
            'context_id' => 'nullable|integer',
        ]);

        $session = $this->startPomodoro->execute(
            userId: (int) Auth::id(),
            plannedMinutes: (int) $validated['planned_minutes'],
            missionId: isset($validated['mission_id']) ? (int) $validated['mission_id'] : null,
            studyGroupSessionId: isset($validated['study_group_session_id']) ? (int) $validated['study_group_session_id'] : null,
            contextType: $validated['context_type'] ?? null,
            contextId: isset($validated['context_id']) ? (int) $validated['context_id'] : null,
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
            'mission_id' => $session->mission_id,
            'context_type' => $session->context_type,
            'context_id' => $session->context_id,
            'started_at' => $session->started_at->setTimezone('America/Lima')->toIso8601String(),
            'paused_at' => $session->paused_at?->setTimezone('America/Lima')->toIso8601String(),
            'total_paused_seconds' => $session->total_paused_seconds,
            'status' => $session->status,
            'focus_minutes' => $session->focus_minutes,
            // El cliente reconstruye el conteo regresivo con esto — no
            // confía en su propio reloj/JS previo, siempre parte de acá
            // (ver Pomodoro/Index.vue).
            'server_now' => CarbonImmutable::now('America/Lima')->toIso8601String(),
        ];
    }
}
