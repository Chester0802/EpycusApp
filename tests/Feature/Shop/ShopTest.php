<?php

declare(strict_types=1);

namespace Tests\Feature\Shop;

use App\Modules\Gamification\Infrastructure\Models\UserProgressModel;
use App\Modules\Identity\Infrastructure\Models\UserModel;
use App\Modules\Shop\Infrastructure\Models\CustomRewardModel;
use App\Modules\Shop\Infrastructure\Models\RewardRedemptionModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ShopTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_view_shop_and_seeds_starter_rewards(): void
    {
        $user = UserModel::factory()->create();

        $response = $this->actingAs($user)->get(route('shop.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Shop/Index')
            ->has('coins')
            ->has('rewards')
            ->has('templates')
        );

        $this->assertDatabaseHas('custom_rewards', [
            'user_id' => $user->id,
            'is_active' => true,
        ]);
    }

    public function test_user_can_create_edit_and_delete_custom_reward(): void
    {
        $user = UserModel::factory()->create();

        // 1. Crear
        $createRes = $this->actingAs($user)->post(route('shop.rewards.store'), [
            'title' => 'Tarde de cine',
            'cost_coins' => 200,
            'icon' => '🎬',
            'category' => 'ocio',
        ]);
        $createRes->assertRedirect();

        $reward = CustomRewardModel::where('user_id', $user->id)->where('title', 'Tarde de cine')->firstOrFail();
        $this->assertEquals(200, $reward->cost_coins);

        // 2. Editar
        $updateRes = $this->actingAs($user)->put(route('shop.rewards.update', ['id' => $reward->id]), [
            'title' => 'Tarde de cine con palomitas',
            'cost_coins' => 250,
            'icon' => '🍿',
            'category' => 'ocio',
        ]);
        $updateRes->assertRedirect();
        $reward->refresh();
        $this->assertEquals('Tarde de cine con palomitas', $reward->title);
        $this->assertEquals(250, $reward->cost_coins);

        // 3. Eliminar
        $deleteRes = $this->actingAs($user)->delete(route('shop.rewards.destroy', ['id' => $reward->id]));
        $deleteRes->assertRedirect();
        $this->assertDatabaseMissing('custom_rewards', ['id' => $reward->id]);
    }

    public function test_user_can_redeem_reward_with_sufficient_coins(): void
    {
        $user = UserModel::factory()->create();

        UserProgressModel::create([
            'user_id' => $user->id,
            'total_xp' => 1000,
            'current_level' => 3,
            'current_phase' => 1,
            'current_streak' => 5,
            'longest_streak' => 5,
            'grace_days_left' => 2,
            'coins' => 500,
        ]);

        $reward = CustomRewardModel::create([
            'user_id' => $user->id,
            'title' => 'Postre favorito',
            'cost_coins' => 150,
            'icon' => '🍨',
            'category' => 'comida',
        ]);

        $redeemRes = $this->actingAs($user)->post(route('shop.redeem', ['id' => $reward->id]));
        $redeemRes->assertRedirect();

        $progress = UserProgressModel::find($user->id);
        $this->assertEquals(350, $progress->coins);

        $redemption = RewardRedemptionModel::where('user_id', $user->id)->firstOrFail();
        $this->assertEquals('Postre favorito', $redemption->title);
        $this->assertEquals('redeemed', $redemption->status);

        // Marcar como disfrutado
        $usedRes = $this->actingAs($user)->patch(route('shop.redemptions.used', ['id' => $redemption->id]));
        $usedRes->assertRedirect();

        $redemption->refresh();
        $this->assertEquals('used', $redemption->status);
        $this->assertNotNull($redemption->used_at);
    }

    public function test_user_cannot_redeem_reward_without_enough_coins(): void
    {
        $user = UserModel::factory()->create();

        UserProgressModel::create([
            'user_id' => $user->id,
            'total_xp' => 100,
            'current_level' => 1,
            'current_phase' => 1,
            'current_streak' => 1,
            'longest_streak' => 1,
            'grace_days_left' => 2,
            'coins' => 50,
        ]);

        $reward = CustomRewardModel::create([
            'user_id' => $user->id,
            'title' => 'Premio caro',
            'cost_coins' => 300,
            'icon' => '🎁',
            'category' => 'ocio',
        ]);

        $redeemRes = $this->actingAs($user)->post(route('shop.redeem', ['id' => $reward->id]));
        $redeemRes->assertSessionHas('error');

        $progress = UserProgressModel::find($user->id);
        $this->assertEquals(50, $progress->coins);
    }
}
