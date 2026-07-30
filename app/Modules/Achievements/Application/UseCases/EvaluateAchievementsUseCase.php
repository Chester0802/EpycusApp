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

        // Recopilar métricas del usuario
        $streak = $this->progressReader->getCurrentStreakFor($userId);
        $level = $this->progressReader->getLevelFor($userId);
        $phase = $this->progressReader->getPhaseFor($userId);

        $pomodoroCount = Schema::hasTable('pomodoro_sessions')
            ? DB::table('pomodoro_sessions')->where('user_id', $userId)->where('status', 'completed')->count()
            : 0;

        $defeatedVillainsCount = Schema::hasTable('user_villains')
            ? DB::table('user_villains')->where('user_id', $userId)->where('hp_remaining', 0)->count()
            : 0;

        $journalCount = Schema::hasTable('journal_entries')
            ? DB::table('journal_entries')->where('user_id', $userId)->count()
            : 0;

        $punctualMissionsCount = Schema::hasTable('missions')
            ? DB::table('missions')->where('user_id', $userId)->whereNotNull('completed_at')->whereNull('deleted_at')->count()
            : 0;

        $newlyUnlocked = [];

        foreach ($allAchievements as $ach) {
            if (in_array($ach->code, $alreadyUnlockedCodes, true)) {
                continue;
            }

            $isQualified = false;

            switch ($ach->code) {
                case 'first_streak_7':
                    $isQualified = ($streak >= 7);
                    break;
                case 'first_streak_14':
                    $isQualified = ($streak >= 14);
                    break;
                case 'first_streak_30':
                    $isQualified = ($streak >= 30);
                    break;
                case 'pomodoro_10':
                    $isQualified = ($pomodoroCount >= 10);
                    break;
                case 'pomodoro_50':
                    $isQualified = ($pomodoroCount >= 50);
                    break;
                case 'pomodoro_100':
                    $isQualified = ($pomodoroCount >= 100);
                    break;
                case 'avatar_phase_3':
                    $isQualified = ($phase >= 3);
                    break;
                case 'avatar_phase_5':
                    $isQualified = ($phase >= 5);
                    break;
                case 'defeat_villain_1':
                    $isQualified = ($defeatedVillainsCount >= 1);
                    break;
                case 'defeat_villain_5':
                    $isQualified = ($defeatedVillainsCount >= 5);
                    break;
                case 'journal_7':
                    $isQualified = ($journalCount >= 7);
                    break;
                case 'journal_30':
                    $isQualified = ($journalCount >= 30);
                    break;
                case 'punctual_5':
                    $isQualified = ($punctualMissionsCount >= 5);
                    break;
            }

            if ($isQualified) {
                // Registrar logro garantizando unicidad
                UserAchievementModel::firstOrCreate([
                    'user_id' => $userId,
                    'achievement_id' => $ach->id,
                ]);

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
