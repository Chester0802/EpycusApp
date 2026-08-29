<?php

declare(strict_types=1);

namespace App\Modules\Gamification\Application\Listeners;

use App\Modules\Gamification\Application\UseCases\AwardXpUseCase;
use App\Modules\Missions\Domain\Events\MissionCompleted;

final class AwardXpFromMissionCompletedListener
{
    public function __construct(private AwardXpUseCase $awardXp) {}

    public function handle(MissionCompleted $event): void
    {
        $baseXp = $event->xpAwarded > 0 ? $event->xpAwarded : (int) config('gamification.xp.mission_medium', 30);

        $this->awardXp->execute(
            userId: $event->userId,
            sourceType: 'mission',
            sourceId: $event->missionId,
            baseXp: $baseXp,
            dailyCap: (int) config('gamification.daily_caps.missions', 3),
            countsTowardStreak: true,
        );
    }
}
