<?php

declare(strict_types=1);

namespace App\Modules\Admin\Application\UseCases;

use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class GenerateDatasetCsvUseCase
{
    public function execute(string $datasetType): StreamedResponse
    {
        $fileName = "epycus_dataset_{$datasetType}_" . date('Ymd_His') . ".csv";

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
        ];

        $callback = function () use ($datasetType) {
            $file = fopen('php://output', 'w');
            // BOM UTF-8 para compatibilidad con Excel
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

            switch ($datasetType) {
                case 'participants':
                    fputcsv($file, ['participant_code', 'level', 'phase', 'total_xp', 'current_streak', 'created_at']);
                    DB::table('participants')
                        ->join('users', 'users.id', '=', 'participants.user_id')
                        ->leftJoin('user_progress', 'user_progress.user_id', '=', 'users.id')
                        ->select(['participants.participant_code', 'user_progress.current_level', 'user_progress.current_phase', 'user_progress.total_xp', 'user_progress.current_streak', 'users.created_at'])
                        ->chunk(100, function ($rows) use ($file) {
                            foreach ($rows as $r) {
                                fputcsv($file, [$r->participant_code, $r->current_level ?? 1, $r->current_phase ?? 1, $r->total_xp ?? 0, $r->current_streak ?? 0, $r->created_at]);
                            }
                        });
                    break;

                case 'habits_pomodoro':
                    fputcsv($file, ['participant_code', 'type', 'planned_or_category', 'status_or_completed', 'created_at']);
                    DB::table('pomodoro_sessions')
                        ->join('participants', 'participants.user_id', '=', 'pomodoro_sessions.user_id')
                        ->select(['participants.participant_code', DB::raw("'pomodoro' as type"), 'planned_minutes', 'status', 'pomodoro_sessions.created_at'])
                        ->chunk(100, function ($rows) use ($file) {
                            foreach ($rows as $r) {
                                fputcsv($file, [$r->participant_code, $r->type, $r->planned_minutes, $r->status, $r->created_at]);
                            }
                        });
                    break;

                case 'telemetry':
                default:
                    fputcsv($file, ['participant_code', 'event_name', 'event_category', 'payload', 'occurred_at']);
                    DB::table('telemetry_events')
                        ->leftJoin('participants', 'participants.user_id', '=', 'telemetry_events.user_id')
                        ->select(['participants.participant_code', 'event_name', 'event_category', 'payload', 'telemetry_events.occurred_at'])
                        ->chunk(200, function ($rows) use ($file) {
                            foreach ($rows as $r) {
                                fputcsv($file, [$r->participant_code ?? 'ANONYMOUS', $r->event_name, $r->event_category, $r->payload, $r->occurred_at]);
                            }
                        });
                    break;
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
