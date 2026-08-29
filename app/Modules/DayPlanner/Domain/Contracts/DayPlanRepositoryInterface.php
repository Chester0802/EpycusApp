<?php

declare(strict_types=1);

namespace App\Modules\DayPlanner\Domain\Contracts;

use App\Modules\DayPlanner\Infrastructure\Models\DailyPlanItemModel;
use App\Modules\DayPlanner\Infrastructure\Models\DailyRoutineModel;
use Illuminate\Database\Eloquent\Collection;

interface DayPlanRepositoryInterface
{
    /**
     * @return Collection<int, DailyRoutineModel>
     */
    public function getActiveRoutinesForUser(int $userId): Collection;

    /**
     * @return Collection<int, DailyPlanItemModel>
     */
    public function getPlanItemsForDate(int $userId, string $date): Collection;

    public function findItemByIdAndUser(int $itemId, int $userId): ?DailyPlanItemModel;

    public function findRoutineByIdAndUser(int $routineId, int $userId): ?DailyRoutineModel;

    /**
     * @param array<string, mixed> $data
     */
    public function createItem(array $data): DailyPlanItemModel;

    /**
     * @param array<string, mixed> $data
     */
    public function updateItem(DailyPlanItemModel $item, array $data): DailyPlanItemModel;

    public function deleteItem(DailyPlanItemModel $item): bool;

    /**
     * @param array<string, mixed> $data
     */
    public function createRoutine(array $data): DailyRoutineModel;

    /**
     * @param array<string, mixed> $data
     */
    public function updateRoutine(DailyRoutineModel $routine, array $data): DailyRoutineModel;

    public function deleteRoutine(DailyRoutineModel $routine): bool;
}
