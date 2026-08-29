<?php

declare(strict_types=1);

namespace App\Modules\Gamification\Application\Listeners;

use App\Modules\Gamification\Application\UseCases\AwardXpUseCase;
use App\Modules\Villains\Domain\Events\VillainDefeated;

final class AwardXpFromVillainDefeatedListener
{
    public function __construct(private AwardXpUseCase $awardXp) {}

    public function handle(VillainDefeated $event): void
    {
        $this->awardXp->execute(
            userId: $event->userId,
            sourceType: 'villain',
            sourceId: $event->instanceId,
            baseXp: (int) config('gamification.xp.villain_defeated', 100),
            dailyCap: 9999,
            countsTowardStreak: false,
        );
    }
}
