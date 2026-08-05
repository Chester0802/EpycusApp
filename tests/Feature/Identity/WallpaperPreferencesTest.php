<?php

declare(strict_types=1);

namespace Tests\Feature\Identity;

use App\Modules\Gamification\Infrastructure\Models\UserProgressModel;
use App\Modules\Identity\Infrastructure\Models\UserModel;
use App\Modules\Identity\Infrastructure\Models\UserPreferencesModel;
use App\Modules\Identity\Infrastructure\Models\UserUnlockedWallpaperModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class WallpaperPreferencesTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_select_default_wallpaper_atardecer(): void
    {
        $user = UserModel::factory()->create();

        $response = $this->actingAs($user)->post('/preferences/wallpaper/select', [
            'wallpaper_key' => 'atardecer',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('user_preferences', [
            'user_id' => $user->id,
            'wallpaper_key' => 'atardecer',
        ]);
    }

    public function test_user_cannot_unlock_wallpaper_without_enough_coins(): void
    {
        $user = UserModel::factory()->create();
        UserProgressModel::create([
            'user_id' => $user->id,
            'total_xp' => 100,
            'current_level' => 1,
            'current_phase' => 1,
            'current_streak' => 1,
            'longest_streak' => 1,
            'grace_days_left' => 3,
            'coins' => 20, // Solo tiene 20 monedas, el fondo cuesta 50
        ]);

        $response = $this->actingAs($user)->post('/preferences/wallpaper/unlock', [
            'wallpaper_key' => 'chica_anime',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');

        // No se debe descontar monedas ni registrar el fondo
        $this->assertDatabaseHas('user_progress', [
            'user_id' => $user->id,
            'coins' => 20,
        ]);
        $this->assertDatabaseMissing('user_unlocked_wallpapers', [
            'user_id' => $user->id,
            'wallpaper_key' => 'chica_anime',
        ]);
    }

    public function test_user_with_enough_coins_can_unlock_and_select_wallpaper(): void
    {
        $user = UserModel::factory()->create();
        UserProgressModel::create([
            'user_id' => $user->id,
            'total_xp' => 500,
            'current_level' => 5,
            'current_phase' => 1,
            'current_streak' => 3,
            'longest_streak' => 3,
            'grace_days_left' => 3,
            'coins' => 100, // Tiene 100 monedas
        ]);

        $response = $this->actingAs($user)->post('/preferences/wallpaper/unlock', [
            'wallpaper_key' => 'lofi_gato',
        ]);

        $response->assertRedirect();

        // Se descuentan 50 monedas -> le quedan 50
        $this->assertDatabaseHas('user_progress', [
            'user_id' => $user->id,
            'coins' => 50,
        ]);

        // Se registra el desbloqueo
        $this->assertDatabaseHas('user_unlocked_wallpapers', [
            'user_id' => $user->id,
            'wallpaper_key' => 'lofi_gato',
        ]);

        // Se establece como fondo activo
        $this->assertDatabaseHas('user_preferences', [
            'user_id' => $user->id,
            'wallpaper_key' => 'lofi_gato',
        ]);
    }

    public function test_user_cannot_select_locked_wallpaper(): void
    {
        $user = UserModel::factory()->create();

        $response = $this->actingAs($user)->post('/preferences/wallpaper/select', [
            'wallpaper_key' => 'dragon_ball',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');

        $this->assertDatabaseMissing('user_preferences', [
            'user_id' => $user->id,
            'wallpaper_key' => 'dragon_ball',
        ]);
    }
}
