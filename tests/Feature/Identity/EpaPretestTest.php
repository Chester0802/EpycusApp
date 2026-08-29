<?php

declare(strict_types=1);

namespace Tests\Feature\Identity;

use App\Modules\Identity\Infrastructure\Models\UserModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class EpaPretestTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_submit_epa_pretest_and_earn_xp(): void
    {
        /** @var UserModel $user */
        $user = UserModel::factory()->create();

        // Crear participante
        DB::table('participants')->insert([
            'user_id' => $user->id,
            'participant_code' => 'P-TEST01',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $payload = [
            'item_2' => 4,
            'item_5' => 4,
            'item_7' => 4,
            'item_10' => 4,
            'item_11' => 4,
            'item_12' => 4,
            'item_13' => 4,
            'item_14' => 4,
        ];

        $response = $this->actingAs($user)->post('/epa/pretest', $payload);

        $response->assertRedirect();

        // Verificar que la respuesta quedó registrada
        $this->assertDatabaseHas('epa_responses', [
            'user_id' => $user->id,
            'participant_code' => 'P-TEST01',
            'phase' => 'pretest',
            'total_score' => 32,
        ]);

        // Verificar telemetría registrada
        $this->assertDatabaseHas('telemetry_events', [
            'user_id' => $user->id,
            'event_name' => 'epa.evaluated',
        ]);
    }

    public function test_user_cannot_submit_epa_pretest_twice(): void
    {
        /** @var UserModel $user */
        $user = UserModel::factory()->create();

        DB::table('participants')->insert([
            'user_id' => $user->id,
            'participant_code' => 'P-TEST02',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $payload = [
            'item_2' => 3,
            'item_5' => 3,
            'item_7' => 3,
            'item_10' => 3,
            'item_11' => 3,
            'item_12' => 3,
            'item_13' => 3,
            'item_14' => 3,
        ];

        // Primer envío exitoso
        $this->actingAs($user)->post('/epa/pretest', $payload)->assertRedirect();

        // Segundo envío falla por idempotencia
        $this->actingAs($user)->post('/epa/pretest', $payload);

        $this->assertDatabaseCount('epa_responses', 1);
    }

    public function test_invalid_item_scores_are_rejected(): void
    {
        /** @var UserModel $user */
        $user = UserModel::factory()->create();

        $invalidPayload = [
            'item_2' => 5, // inválido en escala 1 a 4
            'item_5' => 0, // inválido (mín 1)
            'item_7' => 3,
            'item_10' => 3,
            'item_11' => 3,
            'item_12' => 3,
            'item_13' => 3,
            'item_14' => 3,
        ];

        $response = $this->actingAs($user)->post('/epa/pretest', $invalidPayload);

        $response->assertSessionHasErrors(['item_2', 'item_5']);
        $this->assertDatabaseCount('epa_responses', 0);
    }
}
