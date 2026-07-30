<?php

declare(strict_types=1);

namespace App\Modules\Villains\Domain\Events;

final readonly class VillainDefeated
{
    public function __construct(
        public int $userId,
        public int $instanceId,
        public int $villainId,
        public string $villainCode,
        public int $weekNumber,
        public int $daysTaken,
        public \DateTimeImmutable $defeatedAt,
    ) {}
}
