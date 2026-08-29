<?php

declare(strict_types=1);

namespace Tests\Feature\Achievements;

use App\Modules\Achievements\Application\UseCases\EvaluateAchievementsUseCase;
use App\Modules\Achievements\Application\UseCases\GetUserAchievementsUseCase;
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
        $unlockedCodes = array_column($newlyUnlocked, 'code');
        $this->assertContains('pomodoro_1', $unlockedCodes);
        $this->assertContains('pomodoro_10', $unlockedCodes);

        // Segunda llamada no vuelve a desbloquear (idempotencia)
        $secondRun = $evaluator->execute($user->id);
        $this->assertEmpty($secondRun);
    }

    public function test_evaluate_eisenhower_q2_and_missions_achievements(): void
    {
        $this->seed(AchievementsSeeder::class);

        $user = UserModel::factory()->create();

        // Crear 5 misiones completadas en cuadrante Q2
        for ($i = 0; $i < 5; $i++) {
            DB::table('missions')->insert([
                'user_id' => $user->id,
                'title' => "Misión Q2 #{$i}",
                'difficulty' => 'medium',
                'priority' => 'normal',
                'eisenhower_quadrant' => 'q2',
                'due_date' => now()->addDays(2),
                'completed_at' => now(),
                'days_early_or_late' => -2,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $evaluator = app(EvaluateAchievementsUseCase::class);
        $newlyUnlocked = $evaluator->execute($user->id);

        $unlockedCodes = array_column($newlyUnlocked, 'code');
        $this->assertContains('mission_1', $unlockedCodes);
        $this->assertContains('mission_5', $unlockedCodes);
        $this->assertContains('eisenhower_q2_5', $unlockedCodes);
        $this->assertContains('punctual_5', $unlockedCodes);

        // Validar caso de uso GetUserAchievementsUseCase
        $getUserAchievements = app(GetUserAchievementsUseCase::class);
        $data = $getUserAchievements->execute($user->id);

        $this->assertGreaterThan(0, $data['unlocked_count']);
        $this->assertGreaterThan(0, $data['total_xp_earned']);
    }
}
