<?php

declare(strict_types=1);

namespace App\Modules\Missions\Application\UseCases;

use App\Modules\Missions\Application\DTOs\UpdateMissionDTO;
use App\Modules\Missions\Domain\Contracts\MissionRepositoryInterface;

final class UpdateMissionUseCase
{
    public function __construct(
        private MissionRepositoryInterface $repository,
    ) {}

    public function execute(UpdateMissionDTO $dto): void
    {
        $mission = $this->repository->findByIdAndUser($dto->missionId, $dto->userId);

        if (! $mission) {
            return;
        }

        $data = [
            'title' => $dto->title,
            'description' => $dto->description,
            'difficulty' => $dto->difficulty,
            'priority' => $dto->priority,
            'due_date' => $dto->dueDate,
        ];

        if ($dto->eisenhowerQuadrant !== null) {
            $data['eisenhower_quadrant'] = $dto->eisenhowerQuadrant;
        }

        $this->repository->update($mission, $data);
    }
}
