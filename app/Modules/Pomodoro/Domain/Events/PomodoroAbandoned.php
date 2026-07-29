<?php

declare(strict_types=1);

namespace App\Modules\Pomodoro\Domain\Events;

final readonly class PomodoroAbandoned
{
    public function __construct(
        public int $sessionId,
        public int $userId,
        public int $focusMinutes,
    ) {}
}
