<?php

declare(strict_types=1);

namespace App\Modules\Villains\Application\UseCases;

use App\Modules\Villains\Application\DTOs\AssignVillainDTO;
use App\Modules\Villains\Domain\Contracts\VillainRepositoryInterface;
use App\Modules\Villains\Domain\Events\VillainAssigned;
use App\Modules\Villains\Domain\ValueObjects\VillainCode;
use Illuminate\Contracts\Events\Dispatcher;

final class AssignWeeklyVillainUseCase
{
    public function __construct(
        private VillainRepositoryInterface $repository,
        private Dispatcher $events,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function execute(AssignVillainDTO $dto): array
    {
        VillainCode::from($dto->villainCode);

        $existing = $this->repository->findInstanceByUserAndWeek($dto->userId, $dto->weekNumber);
        if ($existing !== null) {
            return [
                'id' => $existing->id,
                'villain_id' => $existing->villain_id,
                'week_number' => $existing->week_number,
                'already_assigned' => true,
            ];
        }

        $villain = $this->repository->findVillainByCode($dto->villainCode);

        $interventionWeek = min($dto->weekNumber, 10);
        $difficulty = config("gamification.villains.difficulty_by_week.{$interventionWeek}", 1.0);
        $baseHp = (int) config('gamification.villains.base_hp', 100);
        $totalHp = (int) round($baseHp * $difficulty);

        $assignedAt = $this->repository->getMondayForWeek($dto->weekNumber);
        $expiresAt = $this->repository->getSundayForWeek($dto->weekNumber);

        $instance = $this->repository->createInstance([
            'user_id' => $dto->userId,
            'villain_id' => $villain->id,
            'week_number' => $dto->weekNumber,
            'total_hp' => $totalHp,
            'remaining_hp' => $totalHp,
            'status' => 'active',
            'assigned_at' => $assignedAt->format('Y-m-d H:i:s'),
            'expires_at' => $expiresAt->format('Y-m-d H:i:s'),
        ]);

        $this->events->dispatch(new VillainAssigned(
            userId: $dto->userId,
            villainId: $villain->id,
            villainCode: $dto->villainCode,
            weekNumber: $dto->weekNumber,
            assignedAt: $assignedAt,
        ));

        return [
            'id' => $instance->id,
            'villain_id' => $instance->villain_id,
            'week_number' => $instance->week_number,
            'total_hp' => $instance->total_hp,
            'remaining_hp' => $instance->remaining_hp,
            'already_assigned' => false,
        ];
    }
}
