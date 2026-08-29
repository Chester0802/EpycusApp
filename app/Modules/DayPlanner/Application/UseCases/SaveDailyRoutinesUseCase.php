<?php

declare(strict_types=1);

namespace App\Modules\DayPlanner\Application\UseCases;

use App\Modules\DayPlanner\Domain\Contracts\DayPlanRepositoryInterface;
use App\Modules\DayPlanner\Infrastructure\Models\DailyRoutineModel;

final class SaveDailyRoutinesUseCase
{
    public function __construct(
        private readonly DayPlanRepositoryInterface $repository,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public function create(int $userId, array $data): DailyRoutineModel
    {
        return $this->repository->createRoutine(array_merge($data, [
            'user_id' => $userId,
            'is_active' => true,
        ]));
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update(int $routineId, int $userId, array $data): ?DailyRoutineModel
    {
        $routine = $this->repository->findRoutineByIdAndUser($routineId, $userId);
        if ($routine === null) {
            return null;
        }

        return $this->repository->updateRoutine($routine, $data);
    }

    public function delete(int $routineId, int $userId): bool
    {
        $routine = $this->repository->findRoutineByIdAndUser($routineId, $userId);
        if ($routine === null) {
            return false;
        }

        return $this->repository->deleteRoutine($routine);
    }
}
