<?php

declare(strict_types=1);

namespace App\Modules\Missions\Application\UseCases;

use App\Modules\Missions\Infrastructure\Models\SubtaskModel;

final class UpdateSubtaskUseCase
{
    public function execute(int $subtaskId, int $userId, string $title): void
    {
        $subtask = SubtaskModel::with('mission')->findOrFail($subtaskId);

        if ($subtask->mission->user_id !== $userId) {
            throw new \RuntimeException('Unauthorized');
        }

        $subtask->update(['title' => $title]);
    }
}
