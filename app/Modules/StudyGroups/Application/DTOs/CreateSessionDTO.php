<?php

declare(strict_types=1);

namespace App\Modules\StudyGroups\Application\DTOs;

final readonly class CreateSessionDTO
{
    public function __construct(
        public int $hostId,
        public string $name,
        public int $maxSeats,
        public int $focusMinutes = 25,
        public int $breakMinutes = 5,
        public int $cycles = 4,
    ) {}
}
