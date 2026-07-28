<?php

declare(strict_types=1);

namespace App\Modules\Gamification\Domain\Events;

final readonly class XpAwarded
{
    public function __construct(
        public int $userId,
        public int $amount,
        public string $sourceType,
        public bool $wasCapped,
        public int $newTotalXp,
    ) {}
}
