<?php

declare(strict_types=1);

namespace App\Modules\StudyGroups\Application\DTOs;

final readonly class SendMessageDTO
{
    public function __construct(
        public int $userId,
        public int $sessionId,
        public string $body,
    ) {}
}
