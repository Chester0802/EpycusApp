<?php

declare(strict_types=1);

namespace App\Modules\Gamification\Domain\Events;

final readonly class PhaseUnlocked
{
    public function __construct(
        public int $userId,
        public int $newPhase,
    ) {}
}
