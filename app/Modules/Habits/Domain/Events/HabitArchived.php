<?php

declare(strict_types=1);

namespace App\Modules\Habits\Domain\Events;

final class HabitArchived
{
    public function __construct(
        public readonly int $habitId,
        public readonly int $userId,
        public readonly \DateTimeImmutable $occurredAt,
    ) {}
}
