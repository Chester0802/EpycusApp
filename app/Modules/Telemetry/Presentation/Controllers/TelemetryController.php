<?php

declare(strict_types=1);

namespace App\Modules\Telemetry\Presentation\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Telemetry\Application\DTOs\RecordTelemetryEventDTO;
use App\Modules\Telemetry\Application\UseCases\RecordEventBatchUseCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

final class TelemetryController extends Controller
{
    public function __construct(private RecordEventBatchUseCase $recordBatch) {}

    public function storeBatch(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'events' => 'required|array|min:1|max:50',
            'events.*.event_name' => 'required|string|max:64',
            'events.*.event_category' => 'required|string|max:32',
            'events.*.payload' => 'nullable|array',
            'events.*.session_uuid' => 'nullable|string|size:36',
            'events.*.occurred_at' => 'required|string',
        ]);

        $userId = (int) Auth::id();
        $dtos = [];

        foreach ($validated['events'] as $event) {
            $dtos[] = new RecordTelemetryEventDTO(
                userId: $userId,
                eventName: $event['event_name'],
                eventCategory: $event['event_category'],
                payload: $event['payload'] ?? null,
                sessionUuid: $event['session_uuid'] ?? null,
                occurredAt: $event['occurred_at'],
                source: 'web'
            );
        }

        $this->recordBatch->execute($dtos);

        return response()->json(null, 204);
    }
}
