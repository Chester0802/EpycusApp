<?php

declare(strict_types=1);

namespace App\Modules\Telemetry\Infrastructure\Repositories;

use App\Modules\Telemetry\Domain\Contracts\TelemetryRepositoryInterface;
use Illuminate\Support\Facades\DB;

final class EloquentTelemetryRepository implements TelemetryRepositoryInterface
{
    public function recordBatch(array $events): void
    {
        if (empty($events)) {
            return;
        }

        // Formatea el payload a JSON si es array para la inserción masiva
        $rows = array_map(function (array $event): array {
            if (isset($event['payload']) && is_array($event['payload'])) {
                $event['payload'] = json_encode($event['payload'], JSON_UNESCAPED_UNICODE);
            }

            return $event;
        }, $events);

        DB::table('telemetry_events')->insert($rows);
    }
}
