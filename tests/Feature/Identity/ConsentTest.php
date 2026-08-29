<?php

declare(strict_types=1);

namespace Tests\Feature\Identity;

use App\Modules\Identity\Infrastructure\Models\ParticipantModel;
use App\Modules\Identity\Infrastructure\Models\UserModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConsentTest extends TestCase
{
    use RefreshDatabase;

    public function test_registering_creates_a_participant_with_a_pseudonymized_code(): void
    {
        $this->post('/register', [
            'name' => 'Test User',
            'email' => 'participante@example.com',
            'alias' => 'participante_1',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $user = UserModel::where('email', 'participante@example.com')->firstOrFail();
        $participant = ParticipantModel::where('user_id', $user->id)->first();

        $this->assertNotNull($participant);
        $this->assertMatchesRegularExpression('/^EPY-[A-Z0-9]{4}$/', $participant->participant_code);
        $this->assertNull($participant->consent_granted_at);
    }

    public function test_consent_can_be_granted(): void
    {
        $user = UserModel::factory()->participant()->create();
        ParticipantModel::factory()->create(['user_id' => $user->id, 'consent_granted_at' => null]);

        $response = $this->actingAs($user)->post('/consent');

        $response->assertRedirect();
        $this->assertNotNull(
            ParticipantModel::where('user_id', $user->id)->first()->consent_granted_at
        );
    }

    public function test_consent_cannot_be_granted_twice(): void
    {
        $user = UserModel::factory()->participant()->create();
        ParticipantModel::factory()->create(['user_id' => $user->id, 'consent_granted_at' => now()]);

        $response = $this->actingAs($user)
            ->withHeaders(['X-Inertia' => 'true'])
            ->post('/consent');

        $response->assertSessionHasErrors('error');
    }
}
