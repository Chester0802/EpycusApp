<?php

declare(strict_types=1);

namespace Tests\Feature\Achievements;

use App\Modules\Achievements\Application\UseCases\EvaluateAchievementsUseCase;
use App\Modules\Identity\Infrastructure\Models\UserModel;
use Database\Seeders\AchievementsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class AchievementsTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_view_achievements_page(): void
    {
        $this->seed(AchievementsSeeder::class);

        $user = UserModel::factory()->create();

        $response = $this->actingAs($user)->get(route('achievements.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Achievements/Index')
            ->has('summary')
            ->has('achievements')
        );
    }

    public function test_evaluate_achievements_unlocks_and_awards_xp(): void
    {
        $this->seed(AchievementsSeeder::class);

        $user = UserModel::factory()->create();

        // Simular 10 pomodoros completados
        for ($i = 0; $i < 10; $i++) {
            DB::table('pomodoro_sessions')->insert([
                'user_id' => $user->id,
                'status' => 'completed',
                'planned_minutes' => 25,
                'focus_minutes' => 25,
                'started_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $evaluator = app(EvaluateAchievementsUseCase::class);
        $newlyUnlocked = $evaluator->execute($user->id);

        $this->assertNotEmpty($newlyUnlocked);
        $this->assertEquals('pomodoro_10', $newlyUnlocked[0]['code']);

        // Segunda llamada no vuelve a desbloquear (idempotencia)
        $secondRun = $evaluator->execute($user->id);
        $this->assertEmpty($secondRun);
    }
}
