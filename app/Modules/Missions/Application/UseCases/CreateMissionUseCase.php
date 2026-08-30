<?php

declare(strict_types=1);

namespace App\Modules\Missions\Application\UseCases;

use App\Modules\Missions\Application\DTOs\CreateMissionDTO;
use App\Modules\Missions\Domain\Contracts\MissionRepositoryInterface;
use App\Modules\Missions\Domain\Events\MissionCreated;
use App\Modules\Missions\Infrastructure\Models\SubtaskModel;
use Illuminate\Contracts\Events\Dispatcher;

final class CreateMissionUseCase
{
    public function __construct(
        private MissionRepositoryInterface $repository,
        private Dispatcher $events,
    ) {}

    public function execute(CreateMissionDTO $dto): void
    {
        $mission = $this->repository->create([
            'user_id' => $dto->userId,
            'course_id' => $dto->courseId,
            'mission_type' => $dto->missionType,
            'project_phase_id' => $dto->projectPhaseId,
            'title' => $dto->title,
            'description' => $dto->description,
            'difficulty' => $dto->difficulty,
            'priority' => $dto->priority,
            'eisenhower_quadrant' => $dto->eisenhowerQuadrant ?? 'q2',
            'due_date' => $dto->dueDate,
            'planned_date' => $dto->plannedDate,
            'planned_start' => $dto->plannedStart,
            'planned_end' => $dto->plannedEnd,
        ]);

        foreach ($dto->subtasks as $i => $title) {
            SubtaskModel::create([
                'mission_id' => $mission->id,
                'title' => $title,
                'sort_order' => $i,
            ]);
        }

        $this->events->dispatch(new MissionCreated(
            missionId: $mission->id,
            userId: $dto->userId,
            occurredAt: new \DateTimeImmutable,
        ));
    }
}
