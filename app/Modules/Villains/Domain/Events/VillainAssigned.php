<?php

declare(strict_types=1);

namespace App\Modules\Villains\Domain\Events;

final readonly class VillainAssigned
{
    public function __construct(
        public int $userId,
        public int $villainId,
        public string $villainCode,
        public int $weekNumber,
        public \DateTimeImmutable $assignedAt,
    ) {}
}
