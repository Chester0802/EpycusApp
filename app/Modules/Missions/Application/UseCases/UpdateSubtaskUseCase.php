<?php

declare(strict_types=1);

namespace App\Modules\Missions\Application\UseCases;

use App\Modules\Missions\Infrastructure\Models\MissionModel;
use App\Modules\Missions\Infrastructure\Models\SubtaskModel;

final class UpdateSubtaskUseCase
{
    public function execute(int $subtaskId, int $userId, string $title): void
    {
        /** @var SubtaskModel $subtask */
        $subtask = SubtaskModel::with('mission')->findOrFail($subtaskId);
        /** @var MissionModel $mission */
        $mission = $subtask->mission;

        if ($mission->user_id !== $userId) {
            throw new \RuntimeException('Unauthorized');
        }

        $subtask->update(['title' => $title]);
    }
}
