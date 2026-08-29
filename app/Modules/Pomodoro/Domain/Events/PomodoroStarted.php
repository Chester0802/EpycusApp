<?php

declare(strict_types=1);

namespace App\Modules\Pomodoro\Domain\Events;

final readonly class PomodoroStarted
{
    public function __construct(
        public int $sessionId,
        public int $userId,
        public int $plannedMinutes,
        public ?int $studyGroupSessionId = null,
    ) {}
}
