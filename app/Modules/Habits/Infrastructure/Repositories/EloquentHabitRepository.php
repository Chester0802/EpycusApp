<?php

declare(strict_types=1);

namespace App\Modules\Habits\Infrastructure\Repositories;

use App\Modules\Habits\Domain\Contracts\HabitRepositoryInterface;
use App\Modules\Habits\Infrastructure\Models\HabitCompletionModel;
use App\Modules\Habits\Infrastructure\Models\HabitModel;
use Carbon\Carbon;
use Illuminate\Support\Collection;

final class EloquentHabitRepository implements HabitRepositoryInterface
{
    public function getActiveForUser(int $userId): Collection
    {
        return HabitModel::query()
            ->where('user_id', $userId)
            ->where('is_active', true)
            ->with(['completions' => function ($query) {
                $query->where('completed_for', '>=', Carbon::now()->startOfMonth()->subMonth()->toDateString());
            }])
            ->orderBy('id', 'desc')
            ->get();
    }

    public function getArchivedForUser(int $userId): Collection
    {
        return HabitModel::query()
            ->where('user_id', $userId)
            ->where('is_active', false)
            ->orderBy('updated_at', 'desc')
            ->get();
    }

    public function findByIdAndUser(int $habitId, int $userId): ?HabitModel
    {
        return HabitModel::query()
            ->where('id', $habitId)
            ->where('user_id', $userId)
            ->first();
    }

    public function create(array $data): HabitModel
    {
        return HabitModel::create($data);
    }

    public function update(HabitModel $habit, array $data): HabitModel
    {
        $habit->update($data);

        return $habit->fresh();
    }

    public function delete(HabitModel $habit): bool
    {
        return (bool) $habit->delete();
    }

    public function completeForDate(HabitModel $habit, string $date, bool $isLate = false): HabitCompletionModel
    {
        return HabitCompletionModel::create([
            'habit_id' => $habit->id,
            'user_id' => $habit->user_id,
            'completed_for' => $date,
            'completed_at' => Carbon::now()->toDateTimeString(),
            'is_late' => $isLate,
            'created_at' => Carbon::now(),
        ]);
    }

    public function uncompleteForDate(HabitModel $habit, string $date): bool
    {
        return (bool) HabitCompletionModel::query()
            ->where('habit_id', $habit->id)
            ->where('completed_for', $date)
            ->delete();
    }

    public function isCompletedForDate(int $habitId, string $date): bool
    {
        return HabitCompletionModel::query()
            ->where('habit_id', $habitId)
            ->where('completed_for', $date)
            ->exists();
    }
}
