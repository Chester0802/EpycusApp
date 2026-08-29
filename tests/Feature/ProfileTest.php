<?php

namespace Tests\Feature;

use App\Modules\Identity\Infrastructure\Models\UserModel as User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_is_displayed(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get('/profile');

        $response->assertOk();
    }

    public function test_profile_information_can_be_updated(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => 'test@example.com',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $user->refresh();

        $this->assertSame('Test User', $user->name);
        $this->assertSame('test@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
    }

    public function test_email_verification_status_is_unchanged_when_the_email_address_is_unchanged(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => $user->email,
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $this->assertNotNull($user->refresh()->email_verified_at);
    }

    public function test_user_can_delete_their_account(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->delete('/profile', [
                'password' => 'password',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/');

        $this->assertGuest();
        $this->assertNull($user->fresh());
    }

    public function test_correct_password_must_be_provided_to_delete_account_for_regular_user(): void
    {
        $user = User::factory()->create([
            'google_id' => null,
        ]);

        $response = $this
            ->actingAs($user)
            ->from('/profile')
            ->delete('/profile', [
                'password' => 'wrong-password',
            ]);

        $response
            ->assertSessionHasErrors('password')
            ->assertRedirect('/profile');

        $this->assertNotNull($user->fresh());
    }

    public function test_google_user_can_delete_account_without_password(): void
    {
        $user = User::factory()->create([
            'google_id' => '109876543210987654321',
        ]);

        $response = $this
            ->actingAs($user)
            ->delete('/profile');

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/');

        $this->assertGuest();
        $this->assertNull($user->fresh());
    }

    public function test_google_user_cannot_update_password(): void
    {
        $user = User::factory()->create([
            'google_id' => '109876543210987654321',
        ]);

        $response = $this
            ->actingAs($user)
            ->put('/password', [
                'current_password' => 'password',
                'password' => 'NewPassword123!',
                'password_confirmation' => 'NewPassword123!',
            ]);

        $response->assertSessionHas('error');
    }

    public function test_user_can_delete_account_with_all_foreign_key_relationships(): void
    {
        $user = User::factory()->create([
            'google_id' => '1234567890',
        ]);

        \Illuminate\Support\Facades\DB::table('participants')->insert([
            'user_id' => $user->id,
            'participant_code' => 'EPY-TEST',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        \Illuminate\Support\Facades\DB::table('telemetry_events')->insert([
            'user_id' => $user->id,
            'event_name' => 'test_event',
            'event_category' => 'auth',
            'occurred_at' => now(),
            'recorded_at' => now(),
        ]);

        \Illuminate\Support\Facades\DB::table('user_progress')->insert([
            'user_id' => $user->id,
            'total_xp' => 100,
            'current_level' => 1,
            'current_phase' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this
            ->actingAs($user)
            ->delete('/profile');

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/');

        $this->assertGuest();
        $this->assertNull($user->fresh());
        $this->assertDatabaseMissing('participants', ['user_id' => $user->id]);
        $this->assertDatabaseMissing('telemetry_events', ['user_id' => $user->id]);
        $this->assertDatabaseMissing('user_progress', ['user_id' => $user->id]);
    }
}
