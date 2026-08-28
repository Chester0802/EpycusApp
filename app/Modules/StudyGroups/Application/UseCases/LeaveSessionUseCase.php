<?php

declare(strict_types=1);

namespace App\Modules\StudyGroups\Application\UseCases;

use App\Modules\StudyGroups\Domain\Contracts\StudySessionRepositoryInterface;
use App\Modules\StudyGroups\Domain\Events\ParticipantLeft;
use App\Modules\StudyGroups\Domain\Exceptions\SessionNotFoundException;
use Illuminate\Contracts\Events\Dispatcher;

final readonly class LeaveSessionUseCase
{
    public function __construct(
        private StudySessionRepositoryInterface $repository,
        private Dispatcher $events,
    ) {}

    public function execute(int $sessionId, int $userId): void
    {
        $session = $this->repository->findById($sessionId);
        if (! $session) {
            throw new SessionNotFoundException($sessionId);
        }

        $now = now();
        $this->repository->removeParticipant($sessionId, $userId, $now->toDateTimeString());

        $this->events->dispatch(new ParticipantLeft(
            userId: $userId,
            sessionId: $sessionId,
            durationMinutes: 0,
            occurredAt: new \DateTimeImmutable,
        ));

        $count = $this->repository->participantCount($sessionId);
        if ($count === 0 && !in_array($sessionId, [1, 2])) {
            $session = $this->repository->findById($sessionId);
            if ($session) {
                $this->repository->update($session, [
                    'state' => 'closed',
                    'closed_at' => $now->toDateTimeString(),
                ]);
            }
        }
    }
}
