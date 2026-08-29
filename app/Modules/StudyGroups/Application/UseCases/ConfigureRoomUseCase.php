<?php

declare(strict_types=1);

namespace App\Modules\StudyGroups\Application\UseCases;

use App\Modules\StudyGroups\Domain\Contracts\StudySessionRepositoryInterface;
use App\Modules\StudyGroups\Domain\Exceptions\RoomNotConfigurableException;
use App\Modules\StudyGroups\Domain\Exceptions\SessionNotFoundException;
use App\Modules\StudyGroups\Domain\ValueObjects\RoomPhase;

final readonly class ConfigureRoomUseCase
{
    public function __construct(
        private StudySessionRepositoryInterface $repository,
    ) {}

    public function execute(int $sessionId, int $userId, int $focusMinutes, int $breakMinutes, int $cycles): void
    {
        $session = $this->repository->findById($sessionId);

        if (! $session) {
            throw new SessionNotFoundException($sessionId);
        }

        $phase = RoomPhase::from($session->phase);

        if ($phase->isRunning() || $phase->isCompleted()) {
            throw new RoomNotConfigurableException('No se puede configurar la sala mientras está en ejecución.');
        }

        if ($session->host_id !== $userId) {
            throw new RoomNotConfigurableException('Solo el anfitrión puede configurar la sala.');
        }

        $this->repository->update($session, [
            'focus_minutes' => max(5, min(120, $focusMinutes)),
            'break_minutes' => max(1, min(30, $breakMinutes)),
            'cycles' => max(1, min(20, $cycles)),
        ]);
    }
}
