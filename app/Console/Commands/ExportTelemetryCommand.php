<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

final class ExportTelemetryCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'telemetry:export
                            {--from=2026-09-07 : Fecha inicial (YYYY-MM-DD)}
                            {--to= : Fecha final (YYYY-MM-DD, por defecto hoy)}
                            {--format=csv : Formato de salida (csv)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Exporta los 3 datasets de telemetría (raw, daily_per_user y summary_per_user) formateados para Python';

    public function handle(): int
    {
        $fromDate = (string) $this->option('from');
        $toDate = (string) ($this->option('to') ?: date('Y-m-d'));

        $this->info("Exportando telemetría desde {$fromDate} hasta {$toDate}...");

        $exportDir = storage_path('app/exports');
        if (! File::exists($exportDir)) {
            File::makeDirectory($exportDir, 0755, true);
        }

        $this->exportRawEvents($exportDir, $fromDate, $toDate);
        $this->exportDailyPerUser($exportDir, $fromDate, $toDate);
        $this->exportSummaryPerUser($exportDir, $fromDate, $toDate);

        $this->info('¡Exportación completada exitosamente! Archivos generados en: storage/app/exports/');

        return Command::SUCCESS;
    }

    private function exportRawEvents(string $dir, string $from, string $to): void
    {
        $path = $dir.'/events_raw.csv';
        $file = fopen($path, 'w');
        if (! $file) {
            return;
        }

        // BOM UTF-8
        fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
        fputcsv($file, ['user_code', 'event_name', 'event_category', 'payload', 'session_uuid', 'intervention_day', 'occurred_at', 'recorded_at', 'source']);

        DB::table('telemetry_events')
            ->leftJoin('participants', 'participants.user_id', '=', 'telemetry_events.user_id')
            ->select(['participants.participant_code', 'event_name', 'event_category', 'payload', 'session_uuid', 'intervention_day', 'occurred_at', 'recorded_at', 'source'])
            ->whereBetween(DB::raw('DATE(occurred_at)'), [$from, $to])
            ->orderBy('occurred_at')
            ->chunk(500, function ($rows) use ($file) {
                foreach ($rows as $r) {
                    fputcsv($file, [
                        $r->participant_code ?? 'ANONYMOUS',
                        $r->event_name,
                        $r->event_category,
                        $r->payload,
                        $r->session_uuid,
                        $r->intervention_day,
                        $r->occurred_at,
                        $r->recorded_at,
                        $r->source,
                    ]);
                }
            });

        fclose($file);
        $this->line("  ✓ Generado: {$path}");
    }

    private function exportDailyPerUser(string $dir, string $from, string $to): void
    {
        $path = $dir.'/daily_per_user.csv';
        $file = fopen($path, 'w');
        if (! $file) {
            return;
        }

        fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
        fputcsv($file, [
            'user_code', 'intervention_day', 'date',
            'habits_completed', 'habits_available', 'adherence_rate',
            'pomodoros_started', 'pomodoros_completed', 'focus_minutes_total',
            'missions_completed', 'missions_overdue', 'avg_days_early_or_late',
            'ai_consultations', 'journal_entries', 'mood_score_avg',
            'current_streak', 'xp_earned', 'current_level', 'current_phase',
            'ranking_views', 'group_session_minutes',
            'app_opens', 'total_session_minutes',
        ]);

        // Obtener la matriz de usuarios y fechas
        $participants = DB::table('participants')
            ->join('users', 'users.id', '=', 'participants.user_id')
            ->leftJoin('user_progress', 'user_progress.user_id', '=', 'users.id')
            ->select('participants.user_id', 'participants.participant_code', 'user_progress.current_streak', 'user_progress.current_level', 'user_progress.current_phase')
            ->get();

        foreach ($participants as $p) {
            $dailyStats = DB::table('telemetry_events')
                ->selectRaw('DATE(occurred_at) as date, intervention_day,
                    COUNT(CASE WHEN event_name = "habit.completed" THEN 1 END) as habits_completed,
                    COUNT(CASE WHEN event_name = "pomodoro.started" THEN 1 END) as pomodoros_started,
                    COUNT(CASE WHEN event_name = "pomodoro.completed" THEN 1 END) as pomodoros_completed,
                    COUNT(CASE WHEN event_name = "mission.completed" THEN 1 END) as missions_completed,
                    COUNT(CASE WHEN event_name = "mission.overdue" THEN 1 END) as missions_overdue,
                    COUNT(CASE WHEN event_name = "ai.consulted" THEN 1 END) as ai_consultations,
                    COUNT(CASE WHEN event_name = "journal.entry_created" THEN 1 END) as journal_entries,
                    COUNT(CASE WHEN event_name = "ranking.viewed" THEN 1 END) as ranking_views,
                    COUNT(CASE WHEN event_name = "app.session_started" THEN 1 END) as app_opens')
                ->where('user_id', $p->user_id)
                ->whereBetween(DB::raw('DATE(occurred_at)'), [$from, $to])
                ->groupBy(DB::raw('DATE(occurred_at)'), 'intervention_day')
                ->get();

            foreach ($dailyStats as $stat) {
                // Minutos de foco reales
                $focusMinutes = (int) DB::table('pomodoro_sessions')
                    ->where('user_id', $p->user_id)
                    ->where('status', 'completed')
                    ->where(DB::raw('DATE(started_at)'), $stat->date)
                    ->sum('focus_minutes');

                // XP ganado ese día
                $xpEarned = (int) DB::table('xp_transactions')
                    ->where('user_id', $p->user_id)
                    ->where(DB::raw('DATE(created_at)'), $stat->date)
                    ->sum('amount');

                // Promedio mood score
                $moodAvg = DB::table('journal_entries')
                    ->where('user_id', $p->user_id)
                    ->where('date', $stat->date)
                    ->avg('mood_score');

                $habitsAvailable = 5;
                $habitsCompleted = (int) $stat->habits_completed;
                $adherenceRate = round(($habitsCompleted / $habitsAvailable) * 100, 2);

                fputcsv($file, [
                    $p->participant_code,
                    $stat->intervention_day,
                    $stat->date,
                    $habitsCompleted,
                    $habitsAvailable,
                    $adherenceRate,
                    (int) $stat->pomodoros_started,
                    (int) $stat->pomodoros_completed,
                    $focusMinutes,
                    (int) $stat->missions_completed,
                    (int) $stat->missions_overdue,
                    0, // avg_days_early_or_late
                    (int) $stat->ai_consultations,
                    (int) $stat->journal_entries,
                    $moodAvg ? round((float) $moodAvg, 2) : 0,
                    $p->current_streak ?? 0,
                    $xpEarned,
                    $p->current_level ?? 1,
                    $p->current_phase ?? 1,
                    (int) $stat->ranking_views,
                    0, // group_session_minutes
                    (int) $stat->app_opens,
                    $focusMinutes,
                ]);
            }
        }

        fclose($file);
        $this->line("  ✓ Generado: {$path}");
    }

    private function exportSummaryPerUser(string $dir, string $from, string $to): void
    {
        $path = $dir.'/summary_per_user.csv';
        $file = fopen($path, 'w');
        if (! $file) {
            return;
        }

        fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
        fputcsv($file, [
            'user_code', 'total_events', 'total_xp', 'current_level', 'current_phase',
            'current_streak', 'habits_completed_total', 'pomodoros_completed_total',
            'focus_minutes_total', 'missions_completed_total', 'ranking_views_total',
        ]);

        DB::table('participants')
            ->join('users', 'users.id', '=', 'participants.user_id')
            ->leftJoin('user_progress', 'user_progress.user_id', '=', 'users.id')
            ->select(['participants.user_id', 'participants.participant_code', 'user_progress.total_xp', 'user_progress.current_level', 'user_progress.current_phase', 'user_progress.current_streak'])
            ->orderBy('participants.user_id')
            ->chunk(100, function ($participants) use ($file, $from, $to) {

                foreach ($participants as $p) {
                    $totalEvents = DB::table('telemetry_events')
                        ->where('user_id', $p->user_id)
                        ->whereBetween(DB::raw('DATE(occurred_at)'), [$from, $to])
                        ->count();

                    $habitsCount = DB::table('telemetry_events')
                        ->where('user_id', $p->user_id)
                        ->where('event_name', 'habit.completed')
                        ->whereBetween(DB::raw('DATE(occurred_at)'), [$from, $to])
                        ->count();

                    $pomodorosCount = DB::table('telemetry_events')
                        ->where('user_id', $p->user_id)
                        ->where('event_name', 'pomodoro.completed')
                        ->whereBetween(DB::raw('DATE(occurred_at)'), [$from, $to])
                        ->count();

                    $focusMinutes = (int) DB::table('pomodoro_sessions')
                        ->where('user_id', $p->user_id)
                        ->where('status', 'completed')
                        ->whereBetween(DB::raw('DATE(started_at)'), [$from, $to])
                        ->sum('focus_minutes');

                    $missionsCount = DB::table('telemetry_events')
                        ->where('user_id', $p->user_id)
                        ->where('event_name', 'mission.completed')
                        ->whereBetween(DB::raw('DATE(occurred_at)'), [$from, $to])
                        ->count();

                    $rankingViews = DB::table('telemetry_events')
                        ->where('user_id', $p->user_id)
                        ->where('event_name', 'ranking.viewed')
                        ->whereBetween(DB::raw('DATE(occurred_at)'), [$from, $to])
                        ->count();

                    fputcsv($file, [
                        $p->participant_code,
                        $totalEvents,
                        $p->total_xp ?? 0,
                        $p->current_level ?? 1,
                        $p->current_phase ?? 1,
                        $p->current_streak ?? 0,
                        $habitsCount,
                        $pomodorosCount,
                        $focusMinutes,
                        $missionsCount,
                        $rankingViews,
                    ]);
                }
            });

        fclose($file);
        $this->line("  ✓ Generado: {$path}");
    }
}
