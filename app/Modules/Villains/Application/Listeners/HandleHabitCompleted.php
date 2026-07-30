<?php

declare(strict_types=1);

namespace App\Modules\Villains\Application\Listeners;

use App\Modules\Habits\Domain\Events\HabitCompleted;
use App\Modules\Villains\Application\DTOs\ApplyDamageDTO;
use App\Modules\Villains\Application\UseCases\ApplyDamageUseCase;

final class HandleHabitCompleted
{
    public function __construct(
        private ApplyDamageUseCase $applyDamage,
    ) {}

    public function handle(HabitCompleted $event): void
    {
        $this->applyDamage->execute(new ApplyDamageDTO(
            userId: $event->userId,
            sourceType: 'habit',
            occurredAt: $event->occurredAt,
        ));
    }
}
