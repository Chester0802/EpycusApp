<?php

declare(strict_types=1);

namespace App\Modules\StudyGroups\Domain\Events;

final readonly class RoomPhaseCompleted
{
    public function __construct(
        public int $sessionId,
        public string $phase,
        public int $cycle,
        public \DateTimeImmutable $occurredAt,
    ) {}
}
