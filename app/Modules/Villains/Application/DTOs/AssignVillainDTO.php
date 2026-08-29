<?php

declare(strict_types=1);

namespace App\Modules\Villains\Application\DTOs;

final class AssignVillainDTO
{
    public function __construct(
        public int $userId,
        public string $villainCode,
        public int $weekNumber,
        public \DateTimeImmutable $now,
    ) {}
}
