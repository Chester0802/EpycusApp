<?php

declare(strict_types=1);

namespace App\Modules\Telemetry\Domain\Contracts;

interface TelemetryRepositoryInterface
{
    /**
     * Inserta un lote de eventos de telemetría de forma masiva (bulk insert).
     *
     * @param  array<int, array<string, mixed>>  $events
     */
    public function recordBatch(array $events): void;
}
