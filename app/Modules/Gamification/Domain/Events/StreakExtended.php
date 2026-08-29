<?php

declare(strict_types=1);

namespace App\Modules\Gamification\Domain\Events;

final readonly class StreakExtended
{
    public function __construct(
        public int $userId,
        public int $days,
        public float $bonusMultiplier,
    ) {}
}
