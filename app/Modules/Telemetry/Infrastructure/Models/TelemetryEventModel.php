<?php

declare(strict_types=1);

namespace App\Modules\Telemetry\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $user_id
 * @property string $event_name
 * @property string $event_category
 * @property array<string, mixed>|null $payload
 * @property string|null $session_uuid
 * @property int|null $intervention_day
 * @property string $occurred_at
 * @property string $recorded_at
 * @property string $source
 */
final class TelemetryEventModel extends Model
{
    public $timestamps = false;

    protected $table = 'telemetry_events';

    protected $fillable = [
        'user_id',
        'event_name',
        'event_category',
        'payload',
        'session_uuid',
        'intervention_day',
        'occurred_at',
        'recorded_at',
        'source',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'intervention_day' => 'integer',
        ];
    }
}
