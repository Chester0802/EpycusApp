<?php

declare(strict_types=1);

namespace App\Modules\StudyGroups\Domain\Events;

final readonly class RoomCompleted
{
    public function __construct(
        public int $sessionId,
        public int $totalCycles,
        public \DateTimeImmutable $occurredAt,
    ) {}
}
