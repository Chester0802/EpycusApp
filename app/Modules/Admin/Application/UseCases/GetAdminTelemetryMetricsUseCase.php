<?php

declare(strict_types=1);

namespace App\Modules\Admin\Application\UseCases;

use Illuminate\Support\Facades\DB;

final class GetAdminTelemetryMetricsUseCase
{
    /**
     * @return array<string, mixed>
     */
    public function execute(): array
    {
        $totalEvents = DB::table('telemetry_events')->count();

        $eventsByCategory = DB::table('telemetry_events')
            ->select('event_category as category', DB::raw('count(*) as count'))
            ->groupBy('event_category')
            ->orderBy('count', 'desc')
            ->get()
            ->toArray();

        $eventsByDay = DB::table('telemetry_events')
            ->select(DB::raw('DATE(occurred_at) as date'), DB::raw('count(*) as count'))
            ->groupBy(DB::raw('DATE(occurred_at)'))
            ->orderBy('date', 'desc')
            ->limit(14)
            ->get()
            ->toArray();

        // Log detallado: últimos 200 eventos con usuario identificado
        $recentEvents = DB::table('telemetry_events')
            ->leftJoin('participants', 'participants.user_id', '=', 'telemetry_events.user_id')
            ->leftJoin('users', 'users.id', '=', 'telemetry_events.user_id')
            ->select([
                'participants.participant_code',
                'users.alias',
                'telemetry_events.event_name',
                'telemetry_events.event_category',
                'telemetry_events.occurred_at',
            ])
            ->orderBy('telemetry_events.occurred_at', 'desc')
            ->limit(200)
            ->get()
            ->map(fn ($e) => [
                'participant_code' => $e->participant_code ?? 'ANON',
                'alias'            => $e->alias ?? '—',
                'event_name'       => $e->event_name,
                'event_category'   => $e->event_category,
                'occurred_at'      => $e->occurred_at ? date('d/m/Y H:i', strtotime($e->occurred_at)) : '—',
            ])
            ->toArray();

        // Top usuarios por volumen de eventos
        $topUsers = DB::table('telemetry_events')
            ->leftJoin('participants', 'participants.user_id', '=', 'telemetry_events.user_id')
            ->leftJoin('users', 'users.id', '=', 'telemetry_events.user_id')
            ->select([
                'participants.participant_code',
                'users.alias',
                DB::raw('count(*) as total_events'),
            ])
            ->groupBy('participants.participant_code', 'users.alias')
            ->orderBy('total_events', 'desc')
            ->limit(20)
            ->get()
            ->map(fn ($u) => [
                'participant_code' => $u->participant_code ?? 'ANON',
                'alias'            => $u->alias ?? '—',
                'total_events'     => $u->total_events,
            ])
            ->toArray();

        return [
            'total_events'  => $totalEvents,
            'by_category'   => $eventsByCategory,
            'by_day'        => $eventsByDay,
            'recent_events' => $recentEvents,
            'top_users'     => $topUsers,
        ];
    }
}
