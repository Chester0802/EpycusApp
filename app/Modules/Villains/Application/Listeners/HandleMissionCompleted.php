<?php

declare(strict_types=1);

namespace App\Modules\Villains\Application\Listeners;

use App\Modules\Missions\Domain\Events\MissionCompleted;
use App\Modules\Villains\Application\DTOs\ApplyDamageDTO;
use App\Modules\Villains\Application\UseCases\ApplyDamageUseCase;

final class HandleMissionCompleted
{
    public function __construct(
        private ApplyDamageUseCase $applyDamage,
    ) {}

    public function handle(MissionCompleted $event): void
    {
        $this->applyDamage->execute(new ApplyDamageDTO(
            userId: $event->userId,
            sourceType: 'mission',
            occurredAt: $event->occurredAt,
        ));
    }
}
