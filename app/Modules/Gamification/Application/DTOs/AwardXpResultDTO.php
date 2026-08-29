<?php

declare(strict_types=1);

namespace App\Modules\Gamification\Application\DTOs;

final readonly class AwardXpResultDTO
{
    public function __construct(
        public int $xpAwarded,
        public bool $wasCapped,
        public int $newTotalXp,
        public bool $leveledUp,
        public int $newLevel,
        public int $newPhase,
    ) {}
}
