<?php

declare(strict_types=1);

namespace App\Modules\Villains\Application\UseCases;

use App\Modules\Villains\Application\DTOs\ApplyDamageDTO;
use App\Modules\Villains\Domain\Contracts\VillainRepositoryInterface;
use App\Modules\Villains\Domain\Events\VillainDefeated;
use App\Modules\Villains\Domain\Events\VillainWeakened;
use App\Modules\Villains\Domain\ValueObjects\VillainCode;
use Illuminate\Contracts\Events\Dispatcher;

final class ApplyDamageUseCase
{
    public function __construct(
        private VillainRepositoryInterface $repository,
        private Dispatcher $events,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function execute(ApplyDamageDTO $dto): array
    {
        $instance = $this->repository->findActiveInstance($dto->userId);
        if ($instance === null) {
            return ['damage_applied' => false, 'reason' => 'no_active_villain'];
        }

        if ($instance->status !== 'active') {
            return ['damage_applied' => false, 'reason' => 'villain_not_active'];
        }

        if ($instance->expires_at < $dto->occurredAt->format('Y-m-d H:i:s')) {
            return ['damage_applied' => false, 'reason' => 'villain_expired'];
        }

        $villainCode = VillainCode::from($instance->villain->code);

        if (! $villainCode->isWeakTo($dto->sourceType)) {
            return ['damage_applied' => false, 'reason' => 'wrong_source_type'];
        }

        $damagePerAction = (int) config('gamification.villains.damage_per_action', 10);
        $newHp = max(0, $instance->remaining_hp - $damagePerAction);

        $this->repository->updateInstance($instance, [
            'remaining_hp' => $newHp,
        ]);

        $this->events->dispatch(new VillainWeakened(
            userId: $dto->userId,
            instanceId: $instance->id,
            damage: $damagePerAction,
            remainingHp: $newHp,
            sourceType: $dto->sourceType,
            occurredAt: $dto->occurredAt,
        ));

        $result = [
            'damage_applied' => true,
            'damage' => $damagePerAction,
            'remaining_hp' => $newHp,
            'total_hp' => $instance->total_hp,
        ];

        if ($newHp <= 0) {
            $defeatedAt = $dto->occurredAt;
            $assignedAt = new \DateTimeImmutable($instance->assigned_at);
            $daysTaken = (int) $assignedAt->diff($defeatedAt)->format('%a');

            $this->repository->updateInstance($instance, [
                'status' => 'defeated',
                'defeated_at' => $defeatedAt->format('Y-m-d H:i:s'),
                'remaining_hp' => 0,
            ]);

            $this->events->dispatch(new VillainDefeated(
                userId: $dto->userId,
                instanceId: $instance->id,
                villainId: $instance->villain_id,
                villainCode: $instance->villain->code,
                weekNumber: $instance->week_number,
                daysTaken: $daysTaken,
                defeatedAt: $defeatedAt,
            ));

            $result['defeated'] = true;
            $result['days_taken'] = $daysTaken;
        }

        return $result;
    }
}
