<?php

declare(strict_types=1);

namespace App\Modules\Admin\Application\UseCases;

use Illuminate\Support\Facades\DB;

final class GetAdminTelemetryMetricsUseCase
{
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

        return [
            'total_events' => $totalEvents,
            'by_category' => $eventsByCategory,
            'by_day' => $eventsByDay,
        ];
    }
}
