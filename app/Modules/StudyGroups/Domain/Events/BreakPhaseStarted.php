<?php

declare(strict_types=1);

namespace App\Modules\StudyGroups\Domain\Events;

final readonly class BreakPhaseStarted
{
    public function __construct(
        public int $sessionId,
        public int $cycle,
        public \DateTimeImmutable $startedAt,
        public \DateTimeImmutable $endsAt,
    ) {}
}
