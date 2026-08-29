<?php

declare(strict_types=1);

namespace App\Modules\StudyGroups\Domain\Events;

final readonly class ParticipantLeft
{
    public function __construct(
        public int $userId,
        public int $sessionId,
        public int $durationMinutes,
        public \DateTimeImmutable $occurredAt,
    ) {}
}
