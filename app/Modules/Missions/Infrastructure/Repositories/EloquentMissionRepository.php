<?php

declare(strict_types=1);

namespace App\Modules\Missions\Infrastructure\Repositories;

use App\Modules\Missions\Domain\Contracts\MissionRepositoryInterface;
use App\Modules\Missions\Infrastructure\Models\MissionModel;
use Carbon\Carbon;
use Illuminate\Support\Collection;

final class EloquentMissionRepository implements MissionRepositoryInterface
{
    public function findByIdAndUser(int $missionId, int $userId): ?MissionModel
    {
        return MissionModel::query()
            ->where('id', $missionId)
            ->where('user_id', $userId)
            ->with('subtasks')
            ->first();
    }

    public function getActiveForUser(int $userId, string $sortBy = 'default'): Collection
    {
        $query = MissionModel::query()
            ->where('user_id', $userId)
            ->whereNull('completed_at')
            ->with('subtasks');

        return match ($sortBy) {
            'priority' => $query->orderByRaw("CASE priority WHEN 'alta' THEN 1 WHEN 'normal' THEN 2 WHEN 'baja' THEN 3 ELSE 4 END")->orderBy('due_date')->get(),
            'difficulty' => $query->orderByRaw("CASE difficulty WHEN 'hard' THEN 1 WHEN 'medium' THEN 2 WHEN 'easy' THEN 3 ELSE 4 END")->orderBy('due_date')->get(),
            'created_at' => $query->orderBy('created_at', 'desc')->get(),
            default => $query
                ->orderByRaw('CASE
                    WHEN is_overdue = 1 THEN 0
                    WHEN due_date = ? THEN 1
                    WHEN due_date <= ? THEN 2
                    ELSE 3
                END', [Carbon::now()->toDateString(), Carbon::now()->addWeek()->toDateString()])
                ->orderBy('due_date')
                ->orderByRaw("CASE priority WHEN 'alta' THEN 1 WHEN 'normal' THEN 2 WHEN 'baja' THEN 3 ELSE 4 END")
                ->get(),
        };
    }

    public function getCompletedForUser(int $userId): Collection
    {
        return MissionModel::query()
            ->where('user_id', $userId)
            ->whereNotNull('completed_at')
            ->with('subtasks')
            ->orderBy('completed_at', 'desc')
            ->get();
    }

    /** @param array<string, mixed> $data */
    public function create(array $data): MissionModel
    {
        return MissionModel::create($data);
    }

    /** @param array<string, mixed> $data */
    public function update(MissionModel $mission, array $data): MissionModel
    {
        $mission->update($data);

        return $mission->fresh();
    }

    public function delete(MissionModel $mission): bool
    {
        return (bool) $mission->delete();
    }

    public function countCompletedToday(int $userId): int
    {
        return MissionModel::query()
            ->where('user_id', $userId)
            ->whereDate('completed_at', Carbon::now()->toDateString())
            ->count();
    }
}
