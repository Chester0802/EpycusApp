<?php

declare(strict_types=1);

namespace App\Modules\Achievements\Application\UseCases;

use App\Modules\Achievements\Infrastructure\Models\AchievementModel;
use App\Modules\Achievements\Infrastructure\Models\UserAchievementModel;
use App\Shared\Domain\Contracts\UserProgressReaderInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class GetUserAchievementsUseCase
{
    public function __construct(
        private readonly EvaluateAchievementsUseCase $evaluator,
        private readonly UserProgressReaderInterface $progressReader,
    ) {}

    public function execute(int $userId): array
    {
        // 1. Evaluar si hay nuevos logros antes de listar
        $this->evaluator->execute($userId);

        $allAchievements = AchievementModel::all();
        $unlockedRecords = UserAchievementModel::where('user_id', $userId)
            ->get()
            ->keyBy('achievement_id');

        // Métricas para cálculo de barras de progreso
        $streak = $this->progressReader->getCurrentStreakFor($userId);
        $level = $this->progressReader->getLevelFor($userId);
        $phase = $this->progressReader->getPhaseFor($userId);

        $pomodoroCount = Schema::hasTable('pomodoro_sessions')
            ? DB::table('pomodoro_sessions')->where('user_id', $userId)->where('status', 'completed')->count()
            : 0;

        $missionsCount = Schema::hasTable('missions')
            ? DB::table('missions')->where('user_id', $userId)->whereNotNull('completed_at')->whereNull('deleted_at')->count()
            : 0;

        $q2MissionsCount = Schema::hasTable('missions')
            ? DB::table('missions')->where('user_id', $userId)->where('eisenhower_quadrant', 'q2')->whereNotNull('completed_at')->whereNull('deleted_at')->count()
            : 0;

        $punctualMissionsCount = Schema::hasTable('missions')
            ? DB::table('missions')->where('user_id', $userId)->whereNotNull('completed_at')->where('days_early_or_late', '<=', 0)->whereNull('deleted_at')->count()
            : 0;

        $habitsCount = Schema::hasTable('habit_completions')
            ? DB::table('habit_completions')
                ->join('habits', 'habits.id', '=', 'habit_completions.habit_id')
                ->where('habits.user_id', $userId)
                ->count()
            : 0;

        $studyGroupsCount = Schema::hasTable('participants')
            ? DB::table('participants')->where('user_id', $userId)->count()
            : 0;

        $defeatedVillainsCount = Schema::hasTable('user_villains')
            ? DB::table('user_villains')->where('user_id', $userId)->where('hp_remaining', 0)->count()
            : 0;

        $journalCount = Schema::hasTable('journal_entries')
            ? DB::table('journal_entries')->where('user_id', $userId)->count()
            : 0;

        $items = $allAchievements->map(function ($ach) use (
            $unlockedRecords, $streak, $level, $phase, $pomodoroCount,
            $missionsCount, $q2MissionsCount, $punctualMissionsCount,
            $habitsCount, $studyGroupsCount, $defeatedVillainsCount, $journalCount
        ) {
            $record = $unlockedRecords->get($ach->id);
            $isUnlocked = $record !== null;

            [$currentVal, $targetVal, $unit] = match ($ach->code) {
                'first_streak_3' => [$streak, 3, 'días'],
                'first_streak_7' => [$streak, 7, 'días'],
                'first_streak_14' => [$streak, 14, 'días'],
                'first_streak_30' => [$streak, 30, 'días'],

                'pomodoro_1' => [$pomodoroCount, 1, 'sesiones'],
                'pomodoro_10' => [$pomodoroCount, 10, 'sesiones'],
                'pomodoro_50' => [$pomodoroCount, 50, 'sesiones'],
                'pomodoro_100' => [$pomodoroCount, 100, 'sesiones'],

                'mission_1' => [$missionsCount, 1, 'misiones'],
                'mission_5' => [$missionsCount, 5, 'misiones'],
                'mission_20' => [$missionsCount, 20, 'misiones'],
                'eisenhower_q2_5' => [$q2MissionsCount, 5, 'misiones Q2'],
                'punctual_5' => [$punctualMissionsCount, 5, 'a tiempo'],

                'habit_1' => [$habitsCount, 1, 'hábitos'],
                'habit_20' => [$habitsCount, 20, 'hábitos'],
                'habit_50' => [$habitsCount, 50, 'hábitos'],

                'study_group_1' => [$studyGroupsCount, 1, 'sesión'],

                'defeat_villain_1' => [$defeatedVillainsCount, 1, 'villanos'],
                'defeat_villain_5' => [$defeatedVillainsCount, 5, 'villanos'],

                'journal_1' => [$journalCount, 1, 'entradas'],
                'journal_7' => [$journalCount, 7, 'entradas'],
                'journal_30' => [$journalCount, 30, 'entradas'],

                'level_5' => [$level, 5, 'nivel'],
                'level_10' => [$level, 10, 'nivel'],
                'avatar_phase_3' => [$phase, 3, 'fase'],
                'avatar_phase_5' => [$phase, 5, 'fase'],

                default => [0, 1, ''],
            };

            $progressPercent = $isUnlocked
                ? 100
                : ($targetVal > 0 ? (int) min(100, round(($currentVal / $targetVal) * 100)) : 0);

            return [
                'id' => $ach->id,
                'code' => $ach->code,
                'name' => $ach->name,
                'description' => $ach->description,
                'category' => $ach->category,
                'icon' => $ach->icon,
                'xp_reward' => $ach->xp_reward,
                'is_unlocked' => $isUnlocked,
                'current_value' => $currentVal,
                'target_value' => $targetVal,
                'unit' => $unit,
                'progress_percent' => $progressPercent,
                'unlocked_at' => ($isUnlocked && $record->unlocked_at)
                    ? date('d/m/Y', strtotime((string) $record->unlocked_at))
                    : null,
            ];
        });

        $totalCount = $items->count();
        $unlockedCount = $items->where('is_unlocked', true)->count();
        $progressPercent = $totalCount > 0 ? (int) round(($unlockedCount / $totalCount) * 100) : 0;
        $totalXpEarned = $items->where('is_unlocked', true)->sum('xp_reward');

        return [
            'total_count' => $totalCount,
            'unlocked_count' => $unlockedCount,
            'progress_percent' => $progressPercent,
            'total_xp_earned' => $totalXpEarned,
            'achievements' => $items->values()->toArray(),
        ];
    }
}
