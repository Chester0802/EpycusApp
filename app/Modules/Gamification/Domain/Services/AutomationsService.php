<?php

declare(strict_types=1);

namespace App\Modules\Gamification\Domain\Services;

use App\Modules\Gamification\Application\UseCases\AwardXpUseCase;
use App\Modules\Gamification\Infrastructure\Models\AutomationModel;
use App\Modules\Missions\Infrastructure\Models\MissionModel;
use App\Modules\Habits\Infrastructure\Models\HabitModel;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

final class AutomationsService
{
    public function __construct(
        private readonly AwardXpUseCase $awardXp,
    ) {}

    public static function defaultAutomations(): array
    {
        return [
            [
                'name' => 'Auto-priorizar Misiones Urgentes a Q1',
                'trigger_event' => 'mission_due_soon',
                'action_type' => 'move_to_q1',
                'config' => ['hours_threshold' => 24],
                'description' => 'Si una misión vence hoy o mañana, se mueve automáticamente al Cuadrante 1 (Hacer YA).',
            ],
            [
                'name' => 'Recompensa por Racha de 7 Días de Hábito',
                'trigger_event' => 'habit_streak_milestone',
                'action_type' => 'award_bonus_xp',
                'config' => ['streak_days' => 7, 'bonus_xp' => 50],
                'description' => 'Al mantener un hábito activo durante 7 días continuos, otorga +50 XP de bonificación.',
            ],
            [
                'name' => 'Descanso Inteligente tras Pomodoro Intenso',
                'trigger_event' => 'pomodoro_finished',
                'action_type' => 'suggest_break',
                'config' => ['min_minutes' => 50, 'break_minutes' => 10],
                'description' => 'Si completas 50+ minutos de enfoque continuo, programa 10 minutos de descanso reparador.',
            ],
        ];
    }

    public function runMissionDueSoonRules(int $userId): int
    {
        $activeRules = AutomationModel::forUser($userId)
            ->active()
            ->where('trigger_event', 'mission_due_soon')
            ->get();

        if ($activeRules->isEmpty()) {
            return 0;
        }

        $now = Carbon::now('America/Lima');
        $threshold = $now->copy()->addHours(24);

        $affectedMissions = MissionModel::where('user_id', $userId)
            ->where('status', '!=', 'completed')
            ->whereNotNull('due_date')
            ->where('due_date', '<=', $threshold->toDateString())
            ->where('eisenhower_quadrant', '!=', 'q1')
            ->update(['eisenhower_quadrant' => 'q1']);

        return $affectedMissions;
    }

    public function runHabitStreakRules(int $userId, HabitModel $habit): void
    {
        $activeRules = AutomationModel::forUser($userId)
            ->active()
            ->where('trigger_event', 'habit_streak_milestone')
            ->get();

        foreach ($activeRules as $rule) {
            $requiredStreak = (int) ($rule->config['streak_days'] ?? 7);
            $bonusXp = (int) ($rule->config['bonus_xp'] ?? 50);

            if ($habit->streak_days > 0 && ($habit->streak_days % $requiredStreak === 0)) {
                $this->awardXp->execute(
                    userId: $userId,
                    sourceType: 'automation_streak',
                    sourceId: $habit->id,
                    baseXp: $bonusXp,
                    dailyCap: 200,
                    countsTowardStreak: false
                );
            }
        }
    }
}
