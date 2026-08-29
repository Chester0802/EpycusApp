<?php

declare(strict_types=1);

namespace App\Modules\Missions\Application\UseCases;

use App\Modules\Missions\Domain\Contracts\MissionRepositoryInterface;
use Carbon\Carbon;

final class UncompleteMissionUseCase
{
    public function __construct(
        private MissionRepositoryInterface $repository,
    ) {}

    public function execute(int $missionId, int $userId): void
    {
        $mission = $this->repository->findByIdAndUser($missionId, $userId);

        if (! $mission || ! $mission->completed_at) {
            return;
        }

        $today = Carbon::now()->toDateString();
        $isOverdue = $mission->due_date ? $mission->due_date < $today : false;

        $this->repository->update($mission, [
            'completed_at' => null,
            'days_early_or_late' => null,
            'xp_awarded' => 0,
            'is_overdue' => $isOverdue,
        ]);
    }
}
