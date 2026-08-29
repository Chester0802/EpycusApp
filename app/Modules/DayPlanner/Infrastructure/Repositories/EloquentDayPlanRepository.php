<?php

declare(strict_types=1);

namespace App\Modules\DayPlanner\Infrastructure\Repositories;

use App\Modules\DayPlanner\Domain\Contracts\DayPlanRepositoryInterface;
use App\Modules\DayPlanner\Infrastructure\Models\DailyPlanItemModel;
use App\Modules\DayPlanner\Infrastructure\Models\DailyRoutineModel;
use Illuminate\Database\Eloquent\Collection;

final class EloquentDayPlanRepository implements DayPlanRepositoryInterface
{
    /**
     * @return Collection<int, DailyRoutineModel>
     */
    public function getActiveRoutinesForUser(int $userId): Collection
    {
        return DailyRoutineModel::where('user_id', $userId)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }

    /**
     * @return Collection<int, DailyPlanItemModel>
     */
    public function getPlanItemsForDate(int $userId, string $date): Collection
    {
        return DailyPlanItemModel::where('user_id', $userId)
            ->whereDate('plan_date', $date)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }

    public function findItemByIdAndUser(int $itemId, int $userId): ?DailyPlanItemModel
    {
        return DailyPlanItemModel::where('id', $itemId)
            ->where('user_id', $userId)
            ->first();
    }

    public function findRoutineByIdAndUser(int $routineId, int $userId): ?DailyRoutineModel
    {
        return DailyRoutineModel::where('id', $routineId)
            ->where('user_id', $userId)
            ->first();
    }

    /**
     * @param array<string, mixed> $data
     */
    public function createItem(array $data): DailyPlanItemModel
    {
        return DailyPlanItemModel::create($data);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function updateItem(DailyPlanItemModel $item, array $data): DailyPlanItemModel
    {
        $item->update($data);

        return $item->fresh();
    }

    public function deleteItem(DailyPlanItemModel $item): bool
    {
        return (bool) $item->delete();
    }

    /**
     * @param array<string, mixed> $data
     */
    public function createRoutine(array $data): DailyRoutineModel
    {
        return DailyRoutineModel::create($data);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function updateRoutine(DailyRoutineModel $routine, array $data): DailyRoutineModel
    {
        $routine->update($data);

        return $routine->fresh();
    }

    public function deleteRoutine(DailyRoutineModel $routine): bool
    {
        return (bool) $routine->delete();
    }
}
