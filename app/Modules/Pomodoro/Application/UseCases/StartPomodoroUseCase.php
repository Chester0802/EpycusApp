<?php

declare(strict_types=1);

namespace App\Modules\Pomodoro\Application\UseCases;

use App\Modules\Pomodoro\Domain\Contracts\PomodoroRepositoryInterface;
use App\Modules\Pomodoro\Domain\Events\PomodoroStarted;
use App\Modules\Pomodoro\Domain\Exceptions\ActiveSessionAlreadyExistsException;
use App\Modules\Pomodoro\Domain\ValueObjects\SessionState;
use App\Modules\Pomodoro\Infrastructure\Models\PomodoroSessionModel;
use Carbon\Carbon;
use Illuminate\Contracts\Events\Dispatcher;

final class StartPomodoroUseCase
{
    public function __construct(
        private PomodoroRepositoryInterface $repository,
        private ResolveStaleSessionUseCase $resolveStale,
        private Dispatcher $events,
    ) {}

    public function execute(int $userId, int $plannedMinutes, ?int $missionId = null, ?int $studyGroupSessionId = null): PomodoroSessionModel
    {
        $resolved = $this->resolveStale->execute($userId);

        if ($resolved->session !== null) {
            throw new ActiveSessionAlreadyExistsException;
        }

        $session = $this->repository->create([
            'user_id' => $userId,
            'mission_id' => $missionId,
            'study_group_session_id' => $studyGroupSessionId,
            'planned_minutes' => $plannedMinutes,
            'started_at' => Carbon::now(),
            'status' => SessionState::RUNNING,
        ]);

        $this->events->dispatch(new PomodoroStarted($session->id, $userId, $plannedMinutes, $studyGroupSessionId));

        return $session;
    }
}
