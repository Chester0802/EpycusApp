<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application\DTOs;

final readonly class CompleteProfileDTO
{
    public function __construct(
        public int $userId,
        public string $career,
        public string $avatarStyle,
        public string $avatarGender,
        public int $cycle,
        public string $institutionType,
        public ?string $alias = null,
    ) {}
}
