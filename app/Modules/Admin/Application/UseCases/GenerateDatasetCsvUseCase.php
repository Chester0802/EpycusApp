<?php

declare(strict_types=1);

namespace App\Modules\Admin\Application\UseCases;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

final class GenerateDatasetCsvUseCase
{
    public function execute(string $datasetType): Response
    {
        $fileName = "epycus_dataset_{$datasetType}_".date('Ymd_His').'.csv';
        $csv      = $this->buildCsv($datasetType);

        return response($csv, 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
            'Content-Length'      => strlen($csv),
            'Cache-Control'       => 'no-cache, no-store, must-revalidate',
            'Pragma'              => 'no-cache',
        ]);
    }

    private function buildCsv(string $datasetType): string
    {
        $handle = fopen('php://temp', 'r+');
        // BOM UTF-8 para compatibilidad con Excel
        fwrite($handle, "\xEF\xBB\xBF");

        switch ($datasetType) {
            case 'participants':
                fputcsv($handle, [
                    'participant_code', 'alias', 'career', 'cycle', 'institution_type',
                    'level', 'phase', 'total_xp', 'current_streak', 'created_at',
                ]);
                DB::table('participants')
                    ->join('users', 'users.id', '=', 'participants.user_id')
                    ->leftJoin('user_progress', 'user_progress.user_id', '=', 'users.id')
                    ->where('users.role', 'student')
                    ->select([
                        'participants.participant_code',
                        'users.alias',
                        'users.career',
                        'users.cycle',
                        'users.institution_type',
                        'user_progress.current_level',
                        'user_progress.current_phase',
                        'user_progress.total_xp',
                        'user_progress.current_streak',
                        'users.created_at',
                    ])
                    ->orderBy('participants.participant_code')
                    ->chunk(100, function ($rows) use ($handle) {
                        foreach ($rows as $r) {
                            fputcsv($handle, [
                                $r->participant_code,
                                $r->alias ?? '',
                                $r->career ?? '',
                                $r->cycle ?? '',
                                $r->institution_type ?? '',
                                $r->current_level ?? 1,
                                $r->current_phase ?? 1,
                                $r->total_xp ?? 0,
                                $r->current_streak ?? 0,
                                $r->created_at,
                            ]);
                        }
                    });
                break;

            case 'habits_pomodoro':
                fputcsv($handle, ['participant_code', 'alias', 'type', 'planned_minutes', 'status', 'focus_minutes', 'created_at']);
                DB::table('pomodoro_sessions')
                    ->join('participants', 'participants.user_id', '=', 'pomodoro_sessions.user_id')
                    ->join('users', 'users.id', '=', 'pomodoro_sessions.user_id')
                    ->select([
                        'participants.participant_code',
                        'users.alias',
                        DB::raw("'pomodoro' as type"),
                        'planned_minutes',
                        'status',
                        'focus_minutes',
                        'pomodoro_sessions.created_at',
                    ])
                    ->orderBy('pomodoro_sessions.id')
                    ->chunk(100, function ($rows) use ($handle) {
                        foreach ($rows as $r) {
                            fputcsv($handle, [
                                $r->participant_code,
                                $r->alias ?? '',
                                $r->type,
                                $r->planned_minutes,
                                $r->status,
                                $r->focus_minutes ?? 0,
                                $r->created_at,
                            ]);
                        }
                    });
                break;

            case 'epa_responses':
                fputcsv($handle, [
                    'participant_code', 'alias', 'phase',
                    'item_2', 'item_5', 'item_7', 'item_10',
                    'item_11', 'item_12', 'item_13', 'item_14',
                    'total_score', 'completed_at',
                ]);
                DB::table('epa_responses')
                    ->leftJoin('participants', 'participants.participant_code', '=', 'epa_responses.participant_code')
                    ->leftJoin('users', 'users.id', '=', 'participants.user_id')
                    ->select([
                        'epa_responses.participant_code',
                        'users.alias',
                        'epa_responses.phase',
                        'epa_responses.item_2', 'epa_responses.item_5', 'epa_responses.item_7', 'epa_responses.item_10',
                        'epa_responses.item_11', 'epa_responses.item_12', 'epa_responses.item_13', 'epa_responses.item_14',
                        'epa_responses.total_score', 'epa_responses.completed_at',
                    ])
                    ->orderBy('epa_responses.id')
                    ->chunk(100, function ($rows) use ($handle) {
                        foreach ($rows as $r) {
                            fputcsv($handle, [
                                $r->participant_code, $r->alias ?? '',
                                $r->phase, $r->item_2, $r->item_5, $r->item_7, $r->item_10,
                                $r->item_11, $r->item_12, $r->item_13, $r->item_14,
                                $r->total_score, $r->completed_at,
                            ]);
                        }
                    });
                break;

            case 'dropout':
                fputcsv($handle, ['participant_code', 'alias', 'career', 'cycle', 'institution_type', 'days_inactive', 'level', 'streak', 'last_active_at']);
                DB::table('participants')
                    ->join('users', 'users.id', '=', 'participants.user_id')
                    ->leftJoin('user_progress', 'user_progress.user_id', '=', 'users.id')
                    ->where('users.role', 'student')
                    ->where('users.updated_at', '<=', now()->subDays(3))
                    ->select([
                        'participants.participant_code',
                        'users.alias',
                        'users.career',
                        'users.cycle',
                        'users.institution_type',
                        'user_progress.current_level',
                        'user_progress.current_streak',
                        'users.updated_at as last_active_at',
                    ])
                    ->orderBy('participants.participant_code')
                    ->chunk(100, function ($rows) use ($handle) {
                        foreach ($rows as $r) {
                            $daysInactive = $r->last_active_at
                                ? (int) round((time() - strtotime($r->last_active_at)) / 86400)
                                : 0;
                            fputcsv($handle, [
                                $r->participant_code,
                                $r->alias ?? '',
                                $r->career ?? '',
                                $r->cycle ?? '',
                                $r->institution_type ?? '',
                                $daysInactive,
                                $r->current_level ?? 1,
                                $r->current_streak ?? 0,
                                $r->last_active_at,
                            ]);
                        }
                    });
                break;

            case 'telemetry':
            default:
                fputcsv($handle, ['participant_code', 'alias', 'event_name', 'event_category', 'payload', 'occurred_at']);
                DB::table('telemetry_events')
                    ->leftJoin('participants', 'participants.user_id', '=', 'telemetry_events.user_id')
                    ->leftJoin('users', 'users.id', '=', 'telemetry_events.user_id')
                    ->select([
                        'participants.participant_code',
                        'users.alias',
                        'telemetry_events.event_name',
                        'telemetry_events.event_category',
                        'telemetry_events.payload',
                        'telemetry_events.occurred_at',
                    ])
                    ->orderBy('telemetry_events.id')
                    ->chunk(200, function ($rows) use ($handle) {
                        foreach ($rows as $r) {
                            fputcsv($handle, [
                                $r->participant_code ?? 'ANONYMOUS',
                                $r->alias ?? '',
                                $r->event_name,
                                $r->event_category,
                                $r->payload,
                                $r->occurred_at,
                            ]);
                        }
                    });
                break;
        }

        rewind($handle);
        $content = stream_get_contents($handle);
        fclose($handle);

        return $content;
    }
}
