<?php

declare(strict_types=1);

namespace App\Modules\Missions\Application\UseCases;

use App\Modules\Missions\Domain\Contracts\MissionRepositoryInterface;
use App\Shared\Domain\Exceptions\ForbiddenException;
use App\Shared\Domain\Exceptions\NotFoundException;
use App\Shared\Domain\Exceptions\ValidationException;

final class ChangeQuadrantUseCase
{
    public function __construct(
        private MissionRepositoryInterface $repository,
    ) {}

    /** @throws NotFoundException|ForbiddenException|ValidationException */
    public function execute(int $missionId, int $userId, string $quadrant): void
    {
        if (! in_array($quadrant, ['q1', 'q2', 'q3', 'q4'], true)) {
            throw new ValidationException('El cuadrante debe ser q1, q2, q3 o q4.');
        }

        $mission = $this->repository->findByIdAndUser($missionId, $userId);

        if (! $mission) {
            throw new NotFoundException('Misión no encontrada.');
        }

        $this->repository->update($mission, [
            'eisenhower_quadrant' => $quadrant,
        ]);
    }
}
