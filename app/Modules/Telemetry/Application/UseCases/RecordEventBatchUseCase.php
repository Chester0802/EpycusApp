<?php

declare(strict_types=1);

namespace App\Modules\Telemetry\Application\UseCases;

use App\Modules\Telemetry\Application\DTOs\RecordTelemetryEventDTO;
use App\Modules\Telemetry\Domain\Contracts\TelemetryRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

final class RecordEventBatchUseCase
{
    /**
     * Fecha oficial de inicio de la intervención de 66 días (07/09/2026).
     */
    private const INTERVENTION_START_DATE = '2026-09-07';

    /**
     * Duración en días de la intervención.
     */
    private const INTERVENTION_DAYS = 66;

    public function __construct(private TelemetryRepositoryInterface $repository) {}

    /**
     * @param  array<int, RecordTelemetryEventDTO>  $dtos
     */
    public function execute(array $dtos): void
    {
        if (empty($dtos)) {
            return;
        }

        $nowFormatted = Carbon::now('UTC')->format('Y-m-d H:i:s.v');
        $rows = [];

        foreach ($dtos as $dto) {
            try {
                $occurredCarbon = Carbon::parse($dto->occurredAt);
                $interventionDay = $this->calculateInterventionDay($occurredCarbon);

                $rows[] = [
                    'user_id' => $dto->userId,
                    'event_name' => $dto->eventName,
                    'event_category' => $dto->eventCategory,
                    'payload' => $dto->payload,
                    'session_uuid' => $dto->sessionUuid,
                    'intervention_day' => $interventionDay,
                    'occurred_at' => $occurredCarbon->format('Y-m-d H:i:s.v'),
                    'recorded_at' => $nowFormatted,
                    'source' => $dto->source,
                ];
            } catch (\Throwable $e) {
                Log::channel('single')->error('Error procesando evento de telemetría', [
                    'event' => $dto->eventName,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if (! empty($rows)) {
            $this->repository->recordBatch($rows);
        }
    }

    private function calculateInterventionDay(Carbon $date): ?int
    {
        $startDate = Carbon::parse(self::INTERVENTION_START_DATE)->startOfDay();
        $targetDate = $date->copy()->startOfDay();

        if ($targetDate->lt($startDate)) {
            return null;
        }

        $diffInDays = (int) $startDate->diffInDays($targetDate) + 1;

        if ($diffInDays > self::INTERVENTION_DAYS) {
            return null;
        }

        return $diffInDays;
    }
}
