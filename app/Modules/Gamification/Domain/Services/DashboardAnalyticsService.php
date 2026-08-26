<?php

declare(strict_types=1);

namespace App\Modules\Gamification\Domain\Services;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

final class DashboardAnalyticsService
{
    /**
     * Obtiene todos los conjuntos de datos analíticos para el Dashboard.
     *
     * @return array{
     *     heatmap: array<int, array{date: string, count: int, level: int, focusMinutes: int, habitsDone: int, missionsDone: int}>,
     *     courseDistribution: array<int, array{name: string, color: string, minutes: int, percentage: int, sessions: int}>,
     *     peakHours: array{labels: array<int, string>, data: array<int, int>, peakWindow: string, peakMinutes: int},
     *     wellbeingTrend: array<int, array{date: string, label: string, energy: ?float, stress: ?float, mood: ?float}>,
     *     villainHistory: array<int, array{week: int, villainName: string, damageDealt: int, totalHp: int, status: string, defeated: bool}>
     * }
     */
    public function getAnalytics(int $userId): array
    {
        return [
            'heatmap' => $this->getActivityHeatmap($userId, 60),
            'courseDistribution' => $this->getCourseDistribution($userId),
            'peakHours' => $this->getPeakFocusHours($userId),
            'wellbeingTrend' => $this->getWellbeingTrend($userId, 14),
            'villainHistory' => $this->getVillainHistory($userId, 4),
        ];
    }

    /**
     * 1. Mapa de Calor de Actividad (últimos 60 días).
     *
     * @return array<int, array{date: string, count: int, level: int, focusMinutes: int, habitsDone: int, missionsDone: int}>
     */
    public function getActivityHeatmap(int $userId, int $days = 60): array
    {
        $tz = new \DateTimeZone('America/Lima');
        $today = CarbonImmutable::now($tz);
        $startDate = $today->subDays($days - 1);

        $daysMap = [];
        for ($i = 0; $i < $days; $i++) {
            $d = $startDate->addDays($i)->format('Y-m-d');
            $daysMap[$d] = [
                'date' => $d,
                'count' => 0,
                'level' => 0,
                'focusMinutes' => 0,
                'habitsDone' => 0,
                'missionsDone' => 0,
            ];
        }

        $startStr = $startDate->format('Y-m-d');
        $endStr = $today->format('Y-m-d');

        // Pomodoro
        $pomodoros = DB::table('pomodoro_sessions')
            ->where('user_id', $userId)
            ->where('status', 'completed')
            ->whereBetween(DB::raw('DATE(started_at)'), [$startStr, $endStr])
            ->selectRaw('DATE(started_at) as date, COUNT(*) as sessions, SUM(COALESCE(focus_minutes, planned_minutes)) as total_minutes')
            ->groupBy(DB::raw('DATE(started_at)'))
            ->get();

        foreach ($pomodoros as $row) {
            if (isset($daysMap[$row->date])) {
                $daysMap[$row->date]['focusMinutes'] = (int) $row->total_minutes;
                $daysMap[$row->date]['count'] += (int) $row->sessions;
            }
        }

        // Hábitos
        $habits = DB::table('habit_completions')
            ->where('user_id', $userId)
            ->whereBetween('completed_for', [$startStr, $endStr])
            ->selectRaw('completed_for as date, COUNT(*) as total')
            ->groupBy('completed_for')
            ->get();

        foreach ($habits as $row) {
            if (isset($daysMap[$row->date])) {
                $daysMap[$row->date]['habitsDone'] = (int) $row->total;
                $daysMap[$row->date]['count'] += (int) $row->total;
            }
        }

        // Misiones
        $missions = DB::table('missions')
            ->where('user_id', $userId)
            ->whereNotNull('completed_at')
            ->whereNull('deleted_at')
            ->whereBetween(DB::raw('DATE(completed_at)'), [$startStr, $endStr])
            ->selectRaw('DATE(completed_at) as date, COUNT(*) as total')
            ->groupBy(DB::raw('DATE(completed_at)'))
            ->get();

        foreach ($missions as $row) {
            if (isset($daysMap[$row->date])) {
                $daysMap[$row->date]['missionsDone'] = (int) $row->total;
                $daysMap[$row->date]['count'] += (int) $row->total;
            }
        }

        // Calcular niveles de intensidad (0 a 4)
        foreach ($daysMap as &$item) {
            $totalActivity = $item['count'];
            $item['level'] = match (true) {
                $totalActivity >= 8 => 4,
                $totalActivity >= 5 => 3,
                $totalActivity >= 3 => 2,
                $totalActivity >= 1 => 1,
                default => 0,
            };
        }

        return array_values($daysMap);
    }

    /**
     * 2. Distribución de Tiempo por Curso / Materia.
     *
     * @return array<int, array{name: string, color: string, minutes: int, percentage: int, sessions: int}>
     */
    public function getCourseDistribution(int $userId): array
    {
        $courses = DB::table('courses')
            ->where('user_id', $userId)
            ->select('id', 'name', 'color')
            ->get();

        $courseStats = DB::table('pomodoro_sessions')
            ->join('missions', 'missions.id', '=', 'pomodoro_sessions.mission_id')
            ->where('pomodoro_sessions.user_id', $userId)
            ->where('pomodoro_sessions.status', 'completed')
            ->whereNotNull('missions.course_id')
            ->selectRaw('missions.course_id, COUNT(*) as sessions, SUM(COALESCE(pomodoro_sessions.focus_minutes, pomodoro_sessions.planned_minutes)) as total_minutes')
            ->groupBy('missions.course_id')
            ->get()
            ->keyBy('course_id');

        $unassignedStats = DB::table('pomodoro_sessions')
            ->leftJoin('missions', 'missions.id', '=', 'pomodoro_sessions.mission_id')
            ->where('pomodoro_sessions.user_id', $userId)
            ->where('pomodoro_sessions.status', 'completed')
            ->where(function ($q) {
                $q->whereNull('pomodoro_sessions.mission_id')
                    ->orWhereNull('missions.course_id');
            })
            ->selectRaw('COUNT(*) as sessions, SUM(COALESCE(pomodoro_sessions.focus_minutes, pomodoro_sessions.planned_minutes)) as total_minutes')
            ->first();

        $unassignedMinutes = (int) ($unassignedStats?->total_minutes ?? 0);
        $unassignedSessions = (int) ($unassignedStats?->sessions ?? 0);

        $totalPomodoros = DB::table('pomodoro_sessions')
            ->where('user_id', $userId)
            ->where('status', 'completed')
            ->selectRaw('COUNT(*) as sessions, SUM(COALESCE(focus_minutes, planned_minutes)) as total_minutes')
            ->first();

        $totalMinutesAll = (int) ($totalPomodoros?->total_minutes ?? 0);

        if ($courses->isEmpty()) {
            return [
                [
                    'name' => 'Estudio General / Autodidacta',
                    'color' => '#0284c7',
                    'minutes' => $totalMinutesAll,
                    'percentage' => 100,
                    'sessions' => (int) ($totalPomodoros?->sessions ?? 0),
                ],
            ];
        }

        $result = [];

        foreach ($courses as $c) {
            $stat = $courseStats->get($c->id);
            $mins = (int) ($stat?->total_minutes ?? 0);
            $sessions = (int) ($stat?->sessions ?? 0);
            $pct = $totalMinutesAll > 0 ? (int) round(($mins / $totalMinutesAll) * 100) : 0;

            $result[] = [
                'name' => $c->name,
                'color' => $c->color ?: '#6366f1',
                'minutes' => $mins,
                'percentage' => $pct,
                'sessions' => $sessions,
            ];
        }

        if ($unassignedMinutes > 0) {
            $pct = $totalMinutesAll > 0 ? (int) round(($unassignedMinutes / $totalMinutesAll) * 100) : 0;
            $result[] = [
                'name' => 'General / Autodidacta',
                'color' => '#0284c7',
                'minutes' => $unassignedMinutes,
                'percentage' => $pct,
                'sessions' => $unassignedSessions,
            ];
        }

        usort($result, fn ($a, $b) => $b['minutes'] <=> $a['minutes']);

        return array_values($result);
    }

    /**
     * 3. Curva de Horas Pico de Enfoque.
     *
     * @return array{labels: array<int, string>, data: array<int, int>, peakWindow: string, peakMinutes: int}
     */
    public function getPeakFocusHours(int $userId): array
    {
        $blocks = [
            '06:00 - 09:00' => ['start' => 6, 'end' => 8, 'minutes' => 0],
            '09:00 - 12:00' => ['start' => 9, 'end' => 11, 'minutes' => 0],
            '12:00 - 15:00' => ['start' => 12, 'end' => 14, 'minutes' => 0],
            '15:00 - 18:00' => ['start' => 15, 'end' => 17, 'minutes' => 0],
            '18:00 - 21:00' => ['start' => 18, 'end' => 20, 'minutes' => 0],
            '21:00 - 24:00' => ['start' => 21, 'end' => 23, 'minutes' => 0],
        ];

        $sessions = DB::table('pomodoro_sessions')
            ->where('user_id', $userId)
            ->where('status', 'completed')
            ->select('started_at', 'focus_minutes', 'planned_minutes')
            ->get();

        foreach ($sessions as $s) {
            $hour = (int) date('H', strtotime((string) $s->started_at));
            $mins = (int) ($s->focus_minutes ?: $s->planned_minutes ?: 25);

            foreach ($blocks as &$block) {
                if ($hour >= $block['start'] && $hour <= $block['end']) {
                    $block['minutes'] += $mins;
                    break;
                }
            }
        }

        $labels = array_keys($blocks);
        $data = array_map(fn ($b) => $b['minutes'], array_values($blocks));

        $maxMinutes = 0;
        $peakWindow = 'Mañana (09:00 - 12:00)';

        foreach ($blocks as $name => $block) {
            if ($block['minutes'] > $maxMinutes) {
                $maxMinutes = $block['minutes'];
                $peakWindow = $name;
            }
        }

        return [
            'labels' => $labels,
            'data' => $data,
            'peakWindow' => $peakWindow,
            'peakMinutes' => $maxMinutes,
        ];
    }

    /**
     * 4. Curva de Equilibrio: Energía vs. Estrés (últimos 14 días).
     *
     * @return array<int, array{date: string, label: string, energy: ?float, stress: ?float, mood: ?float}>
     */
    public function getWellbeingTrend(int $userId, int $days = 14): array
    {
        $tz = new \DateTimeZone('America/Lima');
        $today = CarbonImmutable::now($tz);
        $startDate = $today->subDays($days - 1);

        $daysMap = [];
        $dayNames = ['Do', 'Lu', 'Ma', 'Mi', 'Ju', 'Vi', 'Sá'];

        for ($i = 0; $i < $days; $i++) {
            $d = $startDate->addDays($i);
            $dStr = $d->format('Y-m-d');
            $dayOfWeek = (int) $d->format('w');
            $daysMap[$dStr] = [
                'date' => $dStr,
                'label' => $dayNames[$dayOfWeek] . ' ' . $d->format('d/m'),
                'energy' => null,
                'stress' => null,
                'mood' => null,
            ];
        }

        $entries = DB::table('journal_entries')
            ->where('user_id', $userId)
            ->whereBetween('date', [$startDate->format('Y-m-d'), $today->format('Y-m-d')])
            ->select('date', 'energy', 'stress', 'mood_score')
            ->get();

        foreach ($entries as $e) {
            if (isset($daysMap[$e->date])) {
                $daysMap[$e->date]['energy'] = $e->energy !== null ? (float) $e->energy : null;
                $daysMap[$e->date]['stress'] = $e->stress !== null ? (float) $e->stress : null;
                $daysMap[$e->date]['mood'] = $e->mood_score !== null ? (float) $e->mood_score : null;
            }
        }

        return array_values($daysMap);
    }

    /**
     * 5. Historial de Asalto y Daño a Villanos (últimas 4 semanas).
     *
     * @return array<int, array{week: int, villainName: string, damageDealt: int, totalHp: int, status: string, defeated: bool}>
     */
    public function getVillainHistory(int $userId, int $weeks = 4): array
    {
        $instances = DB::table('villain_instances')
            ->join('villains', 'villains.id', '=', 'villain_instances.villain_id')
            ->where('villain_instances.user_id', $userId)
            ->orderBy('villain_instances.week_number', 'desc')
            ->limit($weeks)
            ->select(
                'villain_instances.week_number',
                'villains.name as villain_name',
                'villain_instances.total_hp',
                'villain_instances.remaining_hp',
                'villain_instances.status'
            )
            ->get();

        $history = [];
        foreach ($instances as $inst) {
            $damage = max(0, (int) $inst->total_hp - (int) $inst->remaining_hp);
            $history[] = [
                'week' => (int) $inst->week_number,
                'villainName' => $inst->villain_name,
                'damageDealt' => $damage,
                'totalHp' => (int) $inst->total_hp,
                'status' => (string) $inst->status,
                'defeated' => $inst->status === 'defeated' || $inst->remaining_hp <= 0,
            ];
        }

        return $history;
    }
}
