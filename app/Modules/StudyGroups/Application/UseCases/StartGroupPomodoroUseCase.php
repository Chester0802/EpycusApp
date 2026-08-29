<?php

declare(strict_types=1);

namespace App\Modules\StudyGroups\Application\UseCases;

use App\Modules\Pomodoro\Application\UseCases\StartPomodoroUseCase;
use App\Modules\Pomodoro\Infrastructure\Models\PomodoroSessionModel;
use App\Modules\StudyGroups\Domain\Contracts\StudySessionRepositoryInterface;
use App\Modules\StudyGroups\Domain\Exceptions\NotInSessionException;
use App\Modules\StudyGroups\Domain\Exceptions\SessionNotFoundException;

final readonly class StartGroupPomodoroUseCase
{
    public function __construct(
        private StudySessionRepositoryInterface $studyRepo,
        private StartPomodoroUseCase $startPomodoro,
    ) {}

    public function execute(int $sessionId, int $userId, int $plannedMinutes): PomodoroSessionModel
    {
        $session = $this->studyRepo->findById($sessionId);

        if (! $session || $session->state === 'closed') {
            throw new SessionNotFoundException($sessionId);
        }

        if (! $this->studyRepo->isUserInSession($sessionId, $userId)) {
            throw new NotInSessionException($sessionId);
        }

        return $this->startPomodoro->execute(
            userId: $userId,
            plannedMinutes: $plannedMinutes,
            studyGroupSessionId: $sessionId,
        );
    }
}
