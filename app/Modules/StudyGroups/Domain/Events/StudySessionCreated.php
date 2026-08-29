<?php

declare(strict_types=1);

namespace App\Modules\StudyGroups\Domain\Events;

final readonly class StudySessionCreated
{
    public function __construct(
        public int $userId,
        public int $sessionId,
        public string $sessionName,
        public \DateTimeImmutable $occurredAt,
    ) {}
}
