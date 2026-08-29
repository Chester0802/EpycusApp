<?php

declare(strict_types=1);

namespace App\Modules\Villains\Application\UseCases;

use App\Modules\Villains\Domain\Contracts\VillainRepositoryInterface;
use App\Modules\Villains\Domain\Events\VillainSurvived;
use Illuminate\Contracts\Events\Dispatcher;

final class ExpireVillainUseCase
{
    public function __construct(
        private VillainRepositoryInterface $repository,
        private Dispatcher $events,
    ) {}

    public function execute(\DateTimeImmutable $now): int
    {
        $expired = $this->repository->getExpiredActiveInstances($now);
        $count = 0;

        foreach ($expired as $instance) {
            $remainingHpPercent = $instance->total_hp > 0
                ? (int) round(($instance->remaining_hp / $instance->total_hp) * 100)
                : 0;

            $this->repository->updateInstance($instance, [
                'status' => 'survived',
            ]);

            $this->events->dispatch(new VillainSurvived(
                userId: $instance->user_id,
                instanceId: $instance->id,
                villainId: $instance->villain_id,
                villainCode: $instance->villain->code,
                weekNumber: $instance->week_number,
                remainingHpPercent: $remainingHpPercent,
                expiredAt: $now,
            ));

            $count++;
        }

        return $count;
    }
}
