<?php

declare(strict_types=1);

namespace App\Modules\StudyGroups\Domain\Events;

final readonly class GroupMessageSent
{
    public function __construct(
        public int $userId,
        public int $sessionId,
        public int $messageId,
        public int $messageLength,
        public \DateTimeImmutable $occurredAt,
    ) {}
}
