<?php

declare(strict_types=1);

namespace App\Modules\Telemetry\Application\DTOs;

final readonly class RecordTelemetryEventDTO
{
    /**
     * @param  array<string, mixed>|null  $payload
     */
    public function __construct(
        public int $userId,
        public string $eventName,
        public string $eventCategory,
        public ?array $payload,
        public ?string $sessionUuid,
        public string $occurredAt,
        public string $source = 'web'
    ) {}
}
