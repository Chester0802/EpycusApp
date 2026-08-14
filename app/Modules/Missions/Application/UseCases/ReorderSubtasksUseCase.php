<?php

declare(strict_types=1);

namespace App\Modules\Missions\Application\UseCases;

use App\Modules\Missions\Domain\Contracts\MissionRepositoryInterface;

final class ReorderSubtasksUseCase
{
    public function __construct(
        private MissionRepositoryInterface $repository,
    ) {}

    /**
     * @param array<int, int> $orderedIds
     */
    public function execute(int $missionId, int $userId, array $orderedIds): void
    {
        $mission = $this->repository->findByIdAndUser($missionId, $userId);

        if (! $mission) {
            throw new \RuntimeException('Mission not found');
        }

        foreach ($orderedIds as $index => $subtaskId) {
            $mission->subtasks()->where('id', $subtaskId)->update(['sort_order' => $index]);
        }
    }
}
