<?php

declare(strict_types=1);

namespace Tests\Feature\Telemetry;

use App\Modules\Identity\Infrastructure\Models\UserModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class TelemetryBatchTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_send_telemetry_batch(): void
    {
        $user = UserModel::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/v1/telemetry/batch', [
            'events' => [
                [
                    'event_name' => 'view_dashboard',
                    'event_category' => 'navigation',
                    'payload' => ['theme' => 'oceano'],
                    'session_uuid' => '00000000-0000-0000-0000-000000000001',
                    'occurred_at' => now()->toISOString(),
                ],
                [
                    'event_name' => 'start_pomodoro',
                    'event_category' => 'pomodoro',
                    'payload' => ['duration_minutes' => 25],
                    'session_uuid' => '00000000-0000-0000-0000-000000000001',
                    'occurred_at' => now()->toISOString(),
                ],
            ],
        ]);

        $response->assertStatus(204);

        $this->assertDatabaseHas('telemetry_events', [
            'user_id' => $user->id,
            'event_name' => 'view_dashboard',
            'event_category' => 'navigation',
        ]);

        $this->assertDatabaseHas('telemetry_events', [
            'user_id' => $user->id,
            'event_name' => 'start_pomodoro',
            'event_category' => 'pomodoro',
        ]);
    }
}
