<?php

declare(strict_types=1);

namespace App\Modules\Missions\Application\UseCases;

use App\Modules\Missions\Domain\Contracts\MissionRepositoryInterface;
use App\Modules\Missions\Infrastructure\Models\SubtaskModel;

final class AddSubtaskUseCase
{
    public function __construct(
        private MissionRepositoryInterface $repository,
    ) {}

    public function execute(int $missionId, int $userId, string $title): SubtaskModel
    {
        $mission = $this->repository->findByIdAndUser($missionId, $userId);

        if (! $mission) {
            throw new \RuntimeException('Mission not found');
        }

        if ($mission->subtasks()->count() >= 20) {
            throw new \RuntimeException('Max 20 subtasks per mission');
        }

        $maxSort = (int) ($mission->subtasks()->max('sort_order') ?? 0);

        /** @var SubtaskModel $subtask */
        $subtask = $mission->subtasks()->create([
            'title' => $title,
            'sort_order' => $maxSort + 1,
        ]);

        return $subtask;
    }
}
