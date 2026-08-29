<?php

declare(strict_types=1);

namespace App\Modules\Missions\Domain\Events;

final readonly class SubtaskCompleted
{
    public function __construct(
        public int $subtaskId,
        public int $missionId,
        public int $userId,
        public int $subtaskNumber,
        public int $totalSubtasks,
        public \DateTimeImmutable $occurredAt,
    ) {}
}
