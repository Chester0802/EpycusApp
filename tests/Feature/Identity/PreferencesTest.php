<?php

declare(strict_types=1);

namespace Tests\Feature\Identity;

use App\Modules\Identity\Infrastructure\Models\UserModel;
use App\Modules\Identity\Infrastructure\Models\UserPreferencesModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PreferencesTest extends TestCase
{
    use RefreshDatabase;

    public function test_registering_creates_default_preferences(): void
    {
        $this->post('/register', [
            'name' => 'Test User',
            'email' => 'prefs@example.com',
            'alias' => 'prefs_user',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $user = UserModel::where('email', 'prefs@example.com')->firstOrFail();
        $preferences = UserPreferencesModel::where('user_id', $user->id)->first();

        $this->assertNotNull($preferences);
        $this->assertSame('neumorphism', $preferences->surface_mode);
        $this->assertFalse($preferences->notifications_enabled);
    }

    public function test_surface_mode_can_be_updated(): void
    {
        $user = UserModel::factory()->participant()->create();
        UserPreferencesModel::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->patch('/preferences', [
            'surface_mode' => 'glass',
        ]);

        $response->assertRedirect();
        $this->assertSame(
            'glass',
            UserPreferencesModel::where('user_id', $user->id)->first()->surface_mode
        );
    }

    public function test_notifications_can_be_enabled(): void
    {
        $user = UserModel::factory()->participant()->create();
        UserPreferencesModel::factory()->create(['user_id' => $user->id, 'notifications_enabled' => false]);

        $response = $this->actingAs($user)->patch('/preferences', [
            'notifications_enabled' => true,
        ]);

        $response->assertRedirect();
        $this->assertTrue(
            UserPreferencesModel::where('user_id', $user->id)->first()->notifications_enabled
        );
    }

    public function test_invalid_surface_mode_is_rejected(): void
    {
        $user = UserModel::factory()->participant()->create();
        UserPreferencesModel::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->patch('/preferences', [
            'surface_mode' => 'not-a-real-mode',
        ]);

        $response->assertSessionHasErrors('surface_mode');
    }
}
