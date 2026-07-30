<?php

declare(strict_types=1);

namespace App\Modules\Villains\Application\Listeners;

use App\Modules\Pomodoro\Domain\Events\PomodoroCompleted;
use App\Modules\Villains\Application\DTOs\ApplyDamageDTO;
use App\Modules\Villains\Application\UseCases\ApplyDamageUseCase;

final class HandlePomodoroCompleted
{
    public function __construct(
        private ApplyDamageUseCase $applyDamage,
    ) {}

    public function handle(PomodoroCompleted $event): void
    {
        $this->applyDamage->execute(new ApplyDamageDTO(
            userId: $event->userId,
            sourceType: 'pomodoro',
            occurredAt: new \DateTimeImmutable,
        ));
    }
}
