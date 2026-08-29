<?php

declare(strict_types=1);

namespace App\Modules\Villains\Application\DTOs;

final class ApplyDamageDTO
{
    public function __construct(
        public int $userId,
        public string $sourceType,
        public \DateTimeImmutable $occurredAt,
    ) {}
}
