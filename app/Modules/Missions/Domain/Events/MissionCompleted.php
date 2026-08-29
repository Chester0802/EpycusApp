<?php

declare(strict_types=1);

namespace App\Modules\Missions\Domain\Events;

final readonly class MissionCompleted
{
    public function __construct(
        public int $missionId,
        public int $userId,
        public int $xpAwarded,
        public ?int $daysEarlyOrLate,
        public \DateTimeImmutable $occurredAt,
    ) {}
}
