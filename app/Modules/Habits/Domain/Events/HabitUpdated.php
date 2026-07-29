<?php

declare(strict_types=1);

namespace App\Modules\Habits\Domain\Events;

final readonly class HabitUpdated
{
    public function __construct(
        public int $habitId,
        public int $userId,
        public string $category,
        public \DateTimeImmutable $occurredAt,
    ) {}
}
