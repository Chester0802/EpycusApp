<?php

declare(strict_types=1);

namespace App\Modules\Identity\Domain\Events;

final readonly class ConsentGranted
{
    public function __construct(
        public int $userId,
        public string $participantCode,
        public \DateTimeImmutable $occurredAt,
    ) {}
}
