<?php

declare(strict_types=1);

namespace App\Modules\Admin\Application\UseCases;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

final class GetAdminDashboardMetricsUseCase
{
    public function __construct(
        private readonly GetAdminDropoutUseCase $getDropout,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function execute(): array
    {
        $todayLima = Carbon::now('America/Lima')->toDateString();
        $todayStartTimestamp = Carbon::now('America/Lima')->startOfDay()->timestamp;

        // 1. Usuarios activos hoy (cualquier actividad hoy en Lima)
        $activeUserIds = collect();

        $sessionUserIds = DB::table('sessions')
            ->whereNotNull('user_id')
            ->where('last_activity', '>=', $todayStartTimestamp)
            ->pluck('user_id');
        $activeUserIds = $activeUserIds->merge($sessionUserIds);

        $habitUserIds = DB::table('habit_completions')
            ->join('habits', 'habits.id', '=', 'habit_completions.habit_id')
            ->where('habit_completions.completed_for', $todayLima)
            ->pluck('habits.user_id');
        $activeUserIds = $activeUserIds->merge($habitUserIds);

        $pomodoroUserIds = DB::table('pomodoro_sessions')
            ->whereDate('started_at', $todayLima)
            ->pluck('user_id');
        $activeUserIds = $activeUserIds->merge($pomodoroUserIds);

        $journalUserIds = DB::table('journal_entries')
            ->where('date', $todayLima)
            ->pluck('user_id');
        $activeUserIds = $activeUserIds->merge($journalUserIds);

        $xpUserIds = DB::table('xp_transactions')
            ->whereDate('created_at', $todayLima)
            ->pluck('user_id');
        $activeUserIds = $activeUserIds->merge($xpUserIds);

        $studentIds = DB::table('users')->where('role', 'student')->pluck('id')->all();
        $activeUsersToday = $activeUserIds->unique()->filter(fn ($id) => in_array($id, $studentIds))->count();

        $totalParticipants = DB::table('participants')->count();

        // 2. Sesiones Pomodoro y Minutos de Foco
        $pomodoroQuery = DB::table('pomodoro_sessions')->where('status', 'completed');
        $totalPomodoros = $pomodoroQuery->count();
        $totalFocusMinutes = (int) $pomodoroQuery->sum(DB::raw('COALESCE(focus_minutes, planned_minutes)'));

        // 3. Hábitos completados en total
        $totalHabitsDone = DB::table('habit_completions')->count();

        // 4. Participantes en riesgo de deserción (3+ días sin actividad real)
        $dropoutList = $this->getDropout->execute();
        $dropoutCount = count($dropoutList);

        // 5. Adherencia media (porcentaje promedio de hábitos diarios completados)
        $avgStreak = (float) (DB::table('user_progress')->avg('current_streak') ?? 0);

        // 6. Misiones (Tasa de éxito)
        $totalMissions = DB::table('missions')->count();
        $completedMissions = DB::table('missions')->whereNotNull('completed_at')->count();

        // 7. Uso de IA Edy (Consultas totales)
        $totalAiQueries = DB::table('ai_messages')->where('role', 'user')->count();

        return [
            'total_participants' => $totalParticipants,
            'active_users_today' => $activeUsersToday,
            'total_pomodoros' => $totalPomodoros,
            'total_focus_minutes' => $totalFocusMinutes,
            'total_habits_done' => $totalHabitsDone,
            'dropout_risk_count' => $dropoutCount,
            'avg_streak_days' => round($avgStreak, 1),
            'total_missions' => $totalMissions,
            'completed_missions' => $completedMissions,
            'total_ai_queries' => $totalAiQueries,
        ];
    }
}
