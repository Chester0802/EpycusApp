<?php

declare(strict_types=1);

namespace App\Modules\Pomodoro\Domain\Events;

final readonly class PomodoroPaused
{
    public function __construct(
        public int $sessionId,
        public int $userId,
    ) {}
}
