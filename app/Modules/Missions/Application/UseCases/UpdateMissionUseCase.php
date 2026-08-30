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
            'course_id' => $dto->courseId,
            'title' => $dto->title,
            'description' => $dto->description,
            'difficulty' => $dto->difficulty,
            'priority' => $dto->priority,
            'due_date' => $dto->dueDate,
            'planned_date' => $dto->plannedDate,
            'planned_start' => $dto->plannedStart,
            'planned_end' => $dto->plannedEnd,
        ];

        if ($dto->eisenhowerQuadrant !== null) {
            $data['eisenhower_quadrant'] = $dto->eisenhowerQuadrant;
        }

        if ($dto->missionType !== null) {
            $data['mission_type'] = $dto->missionType;
        }
        
        if ($dto->projectPhaseId !== null || array_key_exists('projectPhaseId', get_object_vars($dto))) {
            $data['project_phase_id'] = $dto->projectPhaseId;
        }

        $this->repository->update($mission, $data);
    }
}
