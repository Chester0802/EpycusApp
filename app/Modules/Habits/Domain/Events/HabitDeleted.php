<?php

declare(strict_types=1);

namespace App\Modules\Habits\Domain\Events;

final readonly class HabitDeleted
{
    public function __construct(
        public int $habitId,
        public int $userId,
        public \DateTimeImmutable $occurredAt,
    ) {}
}
