<?php

declare(strict_types=1);

namespace App\Modules\AiAssistant\Application\Services;

use App\Shared\Domain\Contracts\UserProgressReaderInterface;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class AiContextBuilderService
{
    public function __construct(
        private readonly UserProgressReaderInterface $progressReader,
    ) {}

    public function buildContext(int $userId): string
    {
        $level = $this->progressReader->getLevelFor($userId);
        $phase = $this->progressReader->getPhaseFor($userId);
        $streak = $this->progressReader->getCurrentStreakFor($userId);

        $today = Carbon::now('America/Lima')->toDateString();
        $sevenDaysAgo = Carbon::now('America/Lima')->subDays(7)->toDateString();
        $currentMonth = Carbon::now('America/Lima')->month;
        $currentYear = Carbon::now('America/Lima')->year;

        // 1. Métricas de Hábitos (hoy)
        $habitsDoneToday = DB::table('habit_completions')
            ->join('habits', 'habits.id', '=', 'habit_completions.habit_id')
            ->where('habits.user_id', $userId)
            ->where('habit_completions.completed_for', $today)
            ->count();

        // 2. Minutos de Foco Pomodoro
        $focusMinutesToday = (int) DB::table('pomodoro_sessions')
            ->where('user_id', $userId)
            ->where('status', 'completed')
            ->whereDate('started_at', $today)
            ->sum(DB::raw('COALESCE(focus_minutes, planned_minutes)'));

        $focusMinutesWeek = (int) DB::table('pomodoro_sessions')
            ->where('user_id', $userId)
            ->where('status', 'completed')
            ->whereDate('started_at', '>=', $sevenDaysAgo)
            ->sum(DB::raw('COALESCE(focus_minutes, planned_minutes)'));

        // 3. Resumen de Bienestar & Diario
        $moodEntries = DB::table('journal_entries')
            ->where('user_id', $userId)
            ->whereDate('created_at', '>=', $sevenDaysAgo)
            ->get(['mood_score', 'tags']);

        $avgMood = $moodEntries->isNotEmpty()
            ? round($moodEntries->avg('mood_score'), 1)
            : 'Sin registros recientes';

        $allTags = [];
        foreach ($moodEntries as $entry) {
            if (! empty($entry->tags)) {
                $decoded = json_decode($entry->tags, true);
                if (is_array($decoded)) {
                    $allTags = array_merge($allTags, $decoded);
                }
            }
        }
        $topTags = ! empty($allTags)
            ? implode(', ', array_slice(array_unique($allTags), 0, 3))
            : 'Ninguna';

        // 4. Misiones Activas y Matriz de Eisenhower
        $activeMissions = DB::table('missions')
            ->leftJoin('courses', 'missions.course_id', '=', 'courses.id')
            ->where('missions.user_id', $userId)
            ->whereNull('missions.completed_at')
            ->whereNull('missions.deleted_at')
            ->get([
                'missions.title', 
                'missions.eisenhower_quadrant', 
                'missions.is_overdue', 
                'courses.name as course_name'
            ]);

        $totalActiveMissions = $activeMissions->count();
        $q1CrisisCount = $activeMissions->where('eisenhower_quadrant', 'q1')->count();
        $q2StrategicCount = $activeMissions->where('eisenhower_quadrant', 'q2')->count();
        $overdueMissionsCount = $activeMissions->where('is_overdue', true)->count();
        
        $missionDetailsList = $activeMissions->take(8)->map(function ($mission) {
            $courseLabel = $mission->course_name ? " (Curso: {$mission->course_name})" : "";
            $status = $mission->is_overdue ? "⚠️ Vencida" : ($mission->eisenhower_quadrant === 'q1' ? "🔥 Urgente Q1" : "📅 Q2");
            return "- {$mission->title}{$courseLabel} [{$status}]";
        })->implode("\n");
        
        $urgentMissionDetails = $activeMissions->isNotEmpty() 
            ? "\nDetalle de misiones pendientes (máximo 8):\n{$missionDetailsList}" 
            : "\nNo hay misiones pendientes específicas.";

        // 5. Plan Diario y Time-Blocking de Hoy
        $dayPlanContext = 'Sin actividades registradas hoy';
        if (Schema::hasTable('daily_plan_items')) {
            $planItems = DB::table('daily_plan_items')
                ->where('user_id', $userId)
                ->where('plan_date', $today)
                ->get(['title', 'status', 'postponed_count']);

            if ($planItems->isNotEmpty()) {
                $doneCount = $planItems->where('status', 'done')->count();
                $postponedCount = $planItems->where('status', 'postponed')->count();
                $totalItems = $planItems->count();
                $dayPlanContext = "{$doneCount}/{$totalItems} completadas, {$postponedCount} postergadas";
            }
        }

        // 6. Estado Financiero del Mes
        $financeContext = 'Sin movimientos registrados este mes';
        if (Schema::hasTable('finance_transactions')) {
            $expenses = (float) DB::table('finance_transactions')
                ->where('user_id', $userId)
                ->where('type', 'expense')
                ->whereMonth('date', $currentMonth)
                ->whereYear('date', $currentYear)
                ->sum('amount');

            $income = (float) DB::table('finance_transactions')
                ->where('user_id', $userId)
                ->where('type', 'income')
                ->whereMonth('date', $currentMonth)
                ->whereYear('date', $currentYear)
                ->sum('amount');

            $netBalance = $income - $expenses;
            $financeContext = sprintf('Gastos: S/ %.2f, Ingresos: S/ %.2f, Balance Neto: S/ %.2f', $expenses, $income, $netBalance);
        }

        // 7. Fitness e Hidratación de Hoy
        $hydrationGlasses = 0;
        if (Schema::hasTable('daily_hydration_logs')) {
            $hydrationGlasses = (int) (DB::table('daily_hydration_logs')
                ->where('user_id', $userId)
                ->where('date', $today)
                ->value('glasses_count') ?? 0);
        }

        $workoutMinutesWeek = 0;
        if (Schema::hasTable('fitness_workout_logs')) {
            $workoutMinutesWeek = (int) DB::table('fitness_workout_logs')
                ->where('user_id', $userId)
                ->whereDate('performed_at', '>=', $sevenDaysAgo)
                ->sum('duration_minutes');
        }

        // 8. Monedas para Canje en Tienda
        $coins = 0;
        if (Schema::hasTable('user_progress')) {
            $coins = (int) (DB::table('user_progress')
                ->where('user_id', $userId)
                ->value('coins') ?? 0);
        }

        // 9. Cursos y Minutos de Pomodoro por Curso (últimos 7 días)
        $coursesContext = 'Sin cursos registrados';
        if (Schema::hasTable('courses')) {
            $coursesData = DB::table('courses')
                ->where('courses.user_id', $userId)
                ->leftJoin('missions', 'missions.course_id', '=', 'courses.id')
                ->leftJoin('pomodoro_sessions', function ($join) use ($sevenDaysAgo) {
                    $join->on('pomodoro_sessions.mission_id', '=', 'missions.id')
                         ->where('pomodoro_sessions.status', '=', 'completed')
                         ->whereDate('pomodoro_sessions.started_at', '>=', $sevenDaysAgo);
                })
                ->select(
                    'courses.name',
                    DB::raw('SUM(COALESCE(pomodoro_sessions.focus_minutes, pomodoro_sessions.planned_minutes)) as total_focus')
                )
                ->groupBy('courses.id', 'courses.name')
                ->get();

            if ($coursesData->isNotEmpty()) {
                $courseStrings = $coursesData->map(function ($c) {
                    $mins = (int) $c->total_focus;
                    return "{$c->name} ({$mins} min foco)";
                })->implode(', ');
                $coursesContext = $courseStrings;
            }
        }

        return sprintf(
            "Contexto Integral del Estudiante (Anónimo y Privado):\n".
            "- Nivel actual: %d (Fase %d) | Racha activa: %d días | Monedas acumuladas: %d 🪙\n".
            "- Hábitos completados hoy: %d\n".
            "- Plan Diario / Time-Blocking hoy: %s\n".
            "- Minutos de foco Pomodoro: %d min hoy (%d min últimos 7 días)\n".
            "- Desglose de cursos activos: %s\n".
            "- Estado de Salud & Fitness: %d/8 vasos de agua hoy | %d min ejercicio últimos 7 días\n".
            "- Estado de Finanzas este mes: %s\n".
            "- Estado emocional reciente: Promedio %s / 5 (Etiquetas: %s)\n".
            '- Misiones activas: %d (En Q1/Crisis: %d, En Q2/Estratégico: %d, Vencidas: %d)%s',
            $level,
            $phase,
            $streak,
            $coins,
            $habitsDoneToday,
            $dayPlanContext,
            $focusMinutesToday,
            $focusMinutesWeek,
            $coursesContext,
            $hydrationGlasses,
            $workoutMinutesWeek,
            $financeContext,
            $avgMood,
            $topTags,
            $totalActiveMissions,
            $q1CrisisCount,
            $q2StrategicCount,
            $overdueMissionsCount,
            $urgentMissionDetails
        );
    }
}
