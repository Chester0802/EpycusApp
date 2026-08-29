<?php

declare(strict_types=1);

namespace App\Modules\Identity\Domain\Events;

final readonly class UserRegistered
{
    public function __construct(
        public int $userId,
        public string $email,
        public string $alias,
        public \DateTimeImmutable $occurredAt,
    ) {}
}
