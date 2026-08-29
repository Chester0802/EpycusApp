<?php

declare(strict_types=1);

namespace Tests\Feature\Fitness;

use App\Modules\Fitness\Infrastructure\Models\DailyHydrationLogModel;
use App\Modules\Fitness\Infrastructure\Models\FitnessExerciseModel;
use App\Modules\Fitness\Infrastructure\Models\FitnessWorkoutLogModel;
use App\Modules\Gamification\Infrastructure\Models\UserProgressModel;
use App\Modules\Identity\Infrastructure\Models\UserModel;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class FitnessTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_view_fitness_page_and_seeds_exercises(): void
    {
        $user = UserModel::factory()->create();

        $response = $this->actingAs($user)->get(route('fitness.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Fitness/Index')
            ->has('overview.exercises')
            ->has('overview.routines')
            ->has('overview.hydration')
            ->has('overview.weekly_stats')
        );

        $this->assertGreaterThan(0, FitnessExerciseModel::count());
    }

    public function test_user_can_log_workout_and_gain_xp(): void
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
            'coins' => 10,
        ]);

        $response = $this->actingAs($user)->post(route('fitness.workouts.store'), [
            'routine_name' => 'Anti-Sedentarismo de Escritorio',
            'duration_minutes' => 15,
            'calories_burned' => 75,
            'notes' => 'Mejoró la postura',
        ]);

        $response->assertRedirect();

        $workout = FitnessWorkoutLogModel::where('user_id', $user->id)->firstOrFail();
        $this->assertEquals('Anti-Sedentarismo de Escritorio', $workout->routine_name);
        $this->assertEquals(15, $workout->duration_minutes);

        $progress = UserProgressModel::find($user->id);
        $this->assertEquals(125, $progress->total_xp); // 100 + 25
    }

    public function test_user_can_increment_and_decrement_hydration(): void
    {
        $user = UserModel::factory()->create();
        $today = Carbon::now('America/Lima')->toDateString();

        // 1. Sumar vaso
        $res1 = $this->actingAs($user)->postJson(route('fitness.hydration.update'), [
            'delta' => 1,
            'date' => $today,
        ]);

        $res1->assertStatus(200);
        $res1->assertJson(['glasses_count' => 1]);

        $log = DailyHydrationLogModel::where('user_id', $user->id)->where('date', $today)->firstOrFail();
        $this->assertEquals(1, $log->glasses_count);

        // 2. Restar vaso
        $res2 = $this->actingAs($user)->postJson(route('fitness.hydration.update'), [
            'delta' => -1,
            'date' => $today,
        ]);

        $res2->assertStatus(200);
        $res2->assertJson(['glasses_count' => 0]);
    }
}
