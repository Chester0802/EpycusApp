<?php

declare(strict_types=1);

namespace App\Modules\StudyGroups\Application\UseCases;

use App\Modules\StudyGroups\Application\Mappers\StudyGroupMapper;
use App\Modules\StudyGroups\Domain\Contracts\StudySessionRepositoryInterface;
use App\Modules\StudyGroups\Domain\Events\ParticipantJoined;
use App\Modules\StudyGroups\Domain\Exceptions\AlreadyInSessionException;
use App\Modules\StudyGroups\Domain\Exceptions\SessionFullException;
use App\Modules\StudyGroups\Domain\Exceptions\SessionNotFoundException;
use Illuminate\Contracts\Events\Dispatcher;

final readonly class JoinSessionUseCase
{
    public function __construct(
        private StudySessionRepositoryInterface $repository,
        private StudyGroupMapper $mapper,
        private Dispatcher $events,
    ) {}

    /** @return array<string, mixed> */
    public function execute(int $sessionId, int $userId): array
    {
        $session = $this->repository->findById($sessionId);

        if (! $session || $session->state === 'closed') {
            throw new SessionNotFoundException($sessionId);
        }

        $activeSessions = $this->repository->findActiveSessionsForUser($userId);
        if ($activeSessions->isNotEmpty()) {
            foreach ($activeSessions as $active) {
                if ($active->id === $sessionId) {
                    // Ya está aquí dentro (idempotente)
                    return $this->mapper->toSessionArray($session);
                }
                // Si es un fantasma en otra sala, lo retiramos
                $this->repository->removeParticipant($active->id, $userId, now()->toDateTimeString());
            }
        }

        $count = $this->repository->participantCount($sessionId);
        if ($count >= $session->max_seats) {
            throw new SessionFullException($sessionId);
        }

        $this->repository->addParticipant($sessionId, $userId, now()->toDateTimeString());

        $this->events->dispatch(new ParticipantJoined(
            userId: $userId,
            sessionId: $sessionId,
            participantCount: $count + 1,
            occurredAt: new \DateTimeImmutable,
        ));

        return $this->mapper->toSessionArray($session);
    }
}
