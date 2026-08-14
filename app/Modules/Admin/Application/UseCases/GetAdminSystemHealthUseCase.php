<?php

declare(strict_types=1);

namespace App\Modules\Admin\Application\UseCases;

use Exception;
use Illuminate\Support\Facades\DB;

final class GetAdminSystemHealthUseCase
{
    public function execute(): array
    {
        $dbStatus = 'OK';
        $dbVersion = 'Unknown';
        try {
            $pdo = DB::connection()->getPdo();
            $dbVersion = $pdo->getAttribute(\PDO::ATTR_SERVER_VERSION);
        } catch (Exception $e) {
            $dbStatus = 'ERROR: '.$e->getMessage();
        }

        $todayAiConsultations = DB::table('ai_quotas')
            ->whereDate('date', date('Y-m-d'))
            ->sum('used_count');

        $telemetryToday = DB::table('telemetry_events')
            ->whereDate('occurred_at', date('Y-m-d'))
            ->count();

        return [
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'db_status' => $dbStatus,
            'db_version' => $dbVersion,
            'ai_consultations_today' => (int) $todayAiConsultations,
            'telemetry_events_today' => (int) $telemetryToday,
            'hostinger_workers_capacity' => '40 workers (Hostinger Premium)',
        ];
    }
}
