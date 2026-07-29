<?php

declare(strict_types=1);

namespace App\Modules\Missions\Application\UseCases;

use App\Modules\Missions\Domain\Contracts\MissionRepositoryInterface;
use App\Modules\Missions\Domain\Events\MissionStarted;
use App\Modules\Missions\Domain\Events\SubtaskCompleted;
use App\Modules\Missions\Infrastructure\Models\SubtaskModel;
use Carbon\Carbon;
use Illuminate\Contracts\Events\Dispatcher;

final class ToggleSubtaskUseCase
{
    public function __construct(
        private MissionRepositoryInterface $repository,
        private Dispatcher $events,
    ) {}

    public function execute(int $subtaskId, int $userId): array
    {
        $subtask = SubtaskModel::with('mission')->findOrFail($subtaskId);

        if ($subtask->mission->user_id !== $userId) {
            throw new \RuntimeException('Unauthorized');
        }

        if ($subtask->mission->completed_at) {
            return ['completed' => false, 'mission_completed' => false];
        }

        $now = Carbon::now();
        $subtask->update([
            'is_completed' => ! $subtask->is_completed,
            'completed_at' => $subtask->is_completed ? null : $now,
        ]);

        if ($subtask->is_completed) {
            $total = $subtask->mission->subtasks()->count();
            $done = $subtask->mission->subtasks()->where('is_completed', true)->count();

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

        if ($subtask->is_completed) {
            $allDone = $subtask->mission->subtasks()
                ->where('is_completed', true)
                ->count() === $subtask->mission->subtasks()->count();

            if ($allDone) {
                (new CompleteMissionUseCase($this->repository, $this->events))->execute(
                    $subtask->mission_id,
                    $userId,
                );
                $missionCompleted = true;
            } else {
                $this->events->dispatch(new MissionStarted(
                    missionId: $subtask->mission_id,
                    userId: $userId,
                    occurredAt: new \DateTimeImmutable,
                ));
            }
        }

        return ['completed' => $subtask->is_completed, 'mission_completed' => $missionCompleted];
    }
}
