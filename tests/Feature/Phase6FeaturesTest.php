<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Modules\Gamification\Domain\Services\AutomationsService;
use App\Modules\Gamification\Infrastructure\Models\AutomationModel;
use App\Modules\Identity\Infrastructure\Models\UserModel;
use App\Modules\Missions\Infrastructure\Models\MissionModel;
use App\Modules\Shop\Infrastructure\Models\RewardRedemptionModel;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class Phase6FeaturesTest extends TestCase
{
    use RefreshDatabase;

    private UserModel $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = UserModel::factory()->create();
    }

    public function test_user_can_review_entertainment_redemption(): void
    {
        $this->actingAs($this->user);

        // 1. Create a redemption
        $redemption = RewardRedemptionModel::create([
            'user_id' => $this->user->id,
            'title' => 'Ver 1 película o anime',
            'cost_coins' => 200,
            'icon' => '🎬',
            'status' => 'redeemed',
            'redeemed_at' => now(),
        ]);

        // 2. Submit entertainment review
        $response = $this->postJson(route('shop.redemptions.review', ['id' => $redemption->id]), [
            'entertainment_title' => 'Interstellar',
            'entertainment_category' => 'movie',
            'rating' => 5,
            'review_text' => 'Una obra maestra de la ciencia ficción, descanso merecido.',
        ]);

        $response->assertOk();

        $this->assertDatabaseHas('reward_redemptions', [
            'id' => $redemption->id,
            'entertainment_title' => 'Interstellar',
            'rating' => 5,
            'status' => 'used',
        ]);
    }

    public function test_user_can_manage_and_run_automations(): void
    {
        $this->actingAs($this->user);

        // 1. Get automations (populates defaults)
        $res = $this->getJson(route('automations.index'));
        $res->assertOk();
        $this->assertGreaterThanOrEqual(3, count($res->json('automations')));

        // 2. Toggle automation
        $auto = AutomationModel::where('user_id', $this->user->id)->first();
        $toggleRes = $this->patchJson(route('automations.toggle', ['id' => $auto->id]));
        $toggleRes->assertOk();
        $this->assertFalse($toggleRes->json('automation.is_active'));

        // Toggle back to active
        $this->patchJson(route('automations.toggle', ['id' => $auto->id]));

        // 3. Create a mission due today in Q2
        $now = Carbon::now('America/Lima');
        $mission = MissionModel::create([
            'user_id' => $this->user->id,
            'title' => 'Entrega de Proyecto Urgente',
            'due_date' => $now->toDateString(),
            'eisenhower_quadrant' => 'q2',
            'status' => 'pending',
            'xp_reward' => 50,
        ]);

        // 4. Run automations service
        $runRes = $this->postJson(route('automations.run'));
        $runRes->assertOk();

        $this->assertDatabaseHas('missions', [
            'id' => $mission->id,
            'eisenhower_quadrant' => 'q1', // Auto-prioritized to Q1!
        ]);
    }
}
