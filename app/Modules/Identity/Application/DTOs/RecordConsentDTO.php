<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application\DTOs;

final readonly class RecordConsentDTO
{
    public function __construct(
        public int $userId,
    ) {}
}
