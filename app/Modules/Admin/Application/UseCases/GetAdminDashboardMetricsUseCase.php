<?php

declare(strict_types=1);

namespace App\Modules\Admin\Application\UseCases;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

final class GetAdminDashboardMetricsUseCase
{
    /**
     * @return array<string, mixed>
     */
    public function execute(): array
    {
        $today = Carbon::now()->toDateString();
        $threeDaysAgo = Carbon::now()->subDays(3)->toDateString();

        // 1. Usuarios activos hoy
        $activeUsersToday = DB::table('users')
            ->whereDate('updated_at', $today)
            ->count();

        $totalParticipants = DB::table('participants')->count();

        // 2. Sesiones Pomodoro completadas en total
        $totalPomodoros = DB::table('pomodoro_sessions')
            ->where('status', 'completed')
            ->count();

        // 3. Hábitos completados en total
        $totalHabitsDone = DB::table('habit_completions')->count();

        // 4. Participantes en riesgo de deserción (3+ días sin actividad)
        $dropoutCount = DB::table('users')
            ->where('role', 'student')
            ->whereDate('updated_at', '<=', $threeDaysAgo)
            ->count();

        // 5. Adherencia media (porcentaje promedio de hábitos diarios completados)
        $avgStreak = (float) (DB::table('user_progress')->avg('current_streak') ?? 0);

        return [
            'total_participants' => $totalParticipants,
            'active_users_today' => $activeUsersToday,
            'total_pomodoros' => $totalPomodoros,
            'total_habits_done' => $totalHabitsDone,
            'dropout_risk_count' => $dropoutCount,
            'avg_streak_days' => round($avgStreak, 1),
        ];
    }
}
