<?php

declare(strict_types=1);

namespace App\Modules\Villains\Domain\Events;

final readonly class VillainWeakened
{
    public function __construct(
        public int $userId,
        public int $instanceId,
        public int $damage,
        public int $remainingHp,
        public string $sourceType,
        public \DateTimeImmutable $occurredAt,
    ) {}
}
