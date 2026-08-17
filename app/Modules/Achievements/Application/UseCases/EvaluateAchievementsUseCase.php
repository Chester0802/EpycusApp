<?php

declare(strict_types=1);

namespace App\Modules\Achievements\Application\UseCases;

use App\Modules\Achievements\Infrastructure\Models\AchievementModel;
use App\Modules\Achievements\Infrastructure\Models\UserAchievementModel;
use App\Shared\Domain\Contracts\UserProgressReaderInterface;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class EvaluateAchievementsUseCase
{
    public function __construct(
        private readonly UserProgressReaderInterface $progressReader
    ) {}

    public function execute(int $userId): array
    {
        $allAchievements = AchievementModel::all();
        $unlockedIds = UserAchievementModel::where('user_id', $userId)
            ->pluck('achievement_id')
            ->toArray();

        $alreadyUnlockedCodes = AchievementModel::whereIn('id', $unlockedIds)
            ->pluck('code')
            ->toArray();

        // 1. Recopilar métricas del usuario
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

        $newlyUnlocked = [];

        foreach ($allAchievements as $ach) {
            if (in_array($ach->code, $alreadyUnlockedCodes, true)) {
                continue;
            }

            $isQualified = match ($ach->code) {
                // Constancia
                'first_streak_3' => ($streak >= 3),
                'first_streak_7' => ($streak >= 7),
                'first_streak_14' => ($streak >= 14),
                'first_streak_30' => ($streak >= 30),

                // Pomodoro
                'pomodoro_1' => ($pomodoroCount >= 1),
                'pomodoro_10' => ($pomodoroCount >= 10),
                'pomodoro_50' => ($pomodoroCount >= 50),
                'pomodoro_100' => ($pomodoroCount >= 100),

                // Misiones
                'mission_1' => ($missionsCount >= 1),
                'mission_5' => ($missionsCount >= 5),
                'mission_20' => ($missionsCount >= 20),
                'eisenhower_q2_5' => ($q2MissionsCount >= 5),
                'punctual_5' => ($punctualMissionsCount >= 5),

                // Hábitos
                'habit_1' => ($habitsCount >= 1),
                'habit_20' => ($habitsCount >= 20),
                'habit_50' => ($habitsCount >= 50),

                // Estudio Grupal
                'study_group_1' => ($studyGroupsCount >= 1),

                // Villanos
                'defeat_villain_1' => ($defeatedVillainsCount >= 1),
                'defeat_villain_5' => ($defeatedVillainsCount >= 5),

                // Bienestar
                'journal_1' => ($journalCount >= 1),
                'journal_7' => ($journalCount >= 7),
                'journal_30' => ($journalCount >= 30),

                // Progresión
                'level_5' => ($level >= 5),
                'level_10' => ($level >= 10),
                'avatar_phase_3' => ($phase >= 3),
                'avatar_phase_5' => ($phase >= 5),

                default => false,
            };

            if ($isQualified) {
                UserAchievementModel::firstOrCreate(
                    [
                        'user_id' => $userId,
                        'achievement_id' => $ach->id,
                    ],
                    [
                        'unlocked_at' => Carbon::now(),
                    ]
                );

                // Otorgar XP al usuario
                DB::table('user_progress')
                    ->where('user_id', $userId)
                    ->increment('total_xp', $ach->xp_reward);

                $newlyUnlocked[] = [
                    'id' => $ach->id,
                    'code' => $ach->code,
                    'name' => $ach->name,
                    'description' => $ach->description,
                    'icon' => $ach->icon,
                    'xp_reward' => $ach->xp_reward,
                ];
            }
        }

        return $newlyUnlocked;
    }
}
