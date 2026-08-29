<?php

declare(strict_types=1);

namespace App\Modules\Missions\Application\UseCases;

use App\Modules\Missions\Domain\Contracts\MissionRepositoryInterface;
use App\Modules\Missions\Domain\Events\MissionStarted;
use App\Modules\Missions\Domain\Events\SubtaskCompleted;
use App\Modules\Missions\Infrastructure\Models\MissionModel;
use App\Modules\Missions\Infrastructure\Models\SubtaskModel;
use Carbon\Carbon;
use Illuminate\Contracts\Events\Dispatcher;

final class ToggleSubtaskUseCase
{
    public function __construct(
        private MissionRepositoryInterface $repository,
        private Dispatcher $events,
    ) {}

    /**
     * @return array{completed: bool, mission_completed: bool}
     */
    public function execute(int $subtaskId, int $userId): array
    {
        /** @var SubtaskModel $subtask */
        $subtask = SubtaskModel::with('mission')->findOrFail($subtaskId);
        /** @var MissionModel $mission */
        $mission = $subtask->mission;

        if ($mission->user_id !== $userId) {
            throw new \RuntimeException('Unauthorized');
        }

        if ($mission->completed_at) {
            return ['completed' => false, 'mission_completed' => false];
        }

        $now = Carbon::now();
        $newCompleted = ! $subtask->is_completed;
        $subtask->update([
            'is_completed' => $newCompleted,
            'completed_at' => $newCompleted ? $now : null,
        ]);

        if ($newCompleted) {
            $total = $mission->subtasks()->count();
            $done = $mission->subtasks()->where('is_completed', true)->count();

            $this->events->dispatch(new SubtaskCompleted(
                subtaskId: $subtask->id,
                missionId: $subtask->mission_id,
                userId: $userId,
                subtaskNumber: $done,
                totalSubtasks: $total,
                occurredAt: new \DateTimeImmutable,
            ));
        }

        $missionCompleted = false;

        if ($newCompleted) {
            $this->events->dispatch(new MissionStarted(
                missionId: $subtask->mission_id,
                userId: $userId,
                occurredAt: new \DateTimeImmutable,
            ));
        }

        return ['completed' => $newCompleted, 'mission_completed' => $missionCompleted];
    }
}
