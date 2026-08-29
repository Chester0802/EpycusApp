<?php

declare(strict_types=1);

namespace App\Modules\Identity\Domain\Events;

final readonly class ProfileCompleted
{
    public function __construct(
        public int $userId,
        public string $career,
        public string $avatarStyle,
        public \DateTimeImmutable $occurredAt,
    ) {}
}
