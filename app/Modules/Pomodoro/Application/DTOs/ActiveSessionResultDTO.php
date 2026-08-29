<?php

declare(strict_types=1);

namespace App\Modules\Pomodoro\Application\DTOs;

use App\Modules\Pomodoro\Infrastructure\Models\PomodoroSessionModel;

final readonly class ActiveSessionResultDTO
{
    public function __construct(
        public ?PomodoroSessionModel $session,
        public ?int $autoCompletedFocusMinutes = null,
    ) {}
}
