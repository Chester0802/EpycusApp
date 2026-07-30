<?php

declare(strict_types=1);

namespace App\Modules\Achievements\Application\UseCases;

use App\Modules\Achievements\Infrastructure\Models\AchievementModel;
use App\Modules\Achievements\Infrastructure\Models\UserAchievementModel;

final class GetUserAchievementsUseCase
{
    public function __construct(
        private readonly EvaluateAchievementsUseCase $evaluator
    ) {}

    public function execute(int $userId): array
    {
        // 1. Evaluar si hay nuevos logros antes de listar
        $this->evaluator->execute($userId);

        $allAchievements = AchievementModel::all();
        $unlockedRecords = UserAchievementModel::where('user_id', $userId)
            ->get()
            ->keyBy('achievement_id');

        $items = $allAchievements->map(function ($ach) use ($unlockedRecords) {
            $record = $unlockedRecords->get($ach->id);
            $isUnlocked = $record !== null;

            return [
                'id' => $ach->id,
                'code' => $ach->code,
                'name' => $ach->name,
                'description' => $ach->description,
                'category' => $ach->category,
                'icon' => $ach->icon,
                'xp_reward' => $ach->xp_reward,
                'is_unlocked' => $isUnlocked,
                'unlocked_at' => $isUnlocked ? date('d/m/Y H:i', strtotime($record->unlocked_at)) : null,
            ];
        });

        $totalCount = $items->count();
        $unlockedCount = $items->where('is_unlocked', true)->count();
        $progressPercent = $totalCount > 0 ? (int) round(($unlockedCount / $totalCount) * 100) : 0;

        return [
            'total_count' => $totalCount,
            'unlocked_count' => $unlockedCount,
            'progress_percent' => $progressPercent,
            'achievements' => $items->values()->toArray(),
        ];
    }
}
