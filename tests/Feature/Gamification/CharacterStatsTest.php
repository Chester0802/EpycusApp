<?php

declare(strict_types=1);

namespace Tests\Feature\Gamification;

use App\Modules\Gamification\Domain\Services\CharacterStatsCalculator;
use App\Modules\Identity\Infrastructure\Models\UserModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

final class CharacterStatsTest extends TestCase
{
    use RefreshDatabase;

    public function test_character_stats_calculator_returns_valid_structure_and_scores(): void
    {
        $user = UserModel::factory()->create([
            'career' => 'ingenieria_sistemas',
            'institution_type' => 'universidad_publica',
        ]);

        // Insertar actividad variada
        DB::table('pomodoro_sessions')->insert([
            'user_id' => $user->id,
            'planned_minutes' => 25,
            'focus_minutes' => 25,
            'status' => 'completed',
            'started_at' => now()->subHours(2),
            'ended_at' => now()->subHours(1),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('missions')->insert([
            'user_id' => $user->id,
            'title' => 'Misión de prueba',
            'completed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $habitId = DB::table('habits')->insertGetId([
            'user_id' => $user->id,
            'title' => 'Hábito de estudio',
            'category' => 'estudio',
            'frequency' => json_encode(['type' => 'daily']),
            'icon' => 'book-open',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('habit_completions')->insert([
            'user_id' => $user->id,
            'habit_id' => $habitId,
            'completed_for' => now()->toDateString(),
            'completed_at' => now(),
            'created_at' => now(),
        ]);

        $calculator = app(CharacterStatsCalculator::class);
        $stats = $calculator->calculate($user->id, level: 3, currentStreak: 2);

        $this->assertArrayHasKey('concentration', $stats);
        $this->assertArrayHasKey('discipline', $stats);
        $this->assertArrayHasKey('resilience', $stats);
        $this->assertArrayHasKey('serenity', $stats);
        $this->assertArrayHasKey('attack', $stats);
        $this->assertArrayHasKey('classTitle', $stats);
        $this->assertArrayHasKey('classDescription', $stats);
        $this->assertArrayHasKey('attributes', $stats);
        $this->assertGreaterThanOrEqual(1, $stats['totalPowerScore']);
        $this->assertNotEmpty($stats['classTitle']);
    }

    public function test_heros_journey_has_ten_phases_covering_fifty_levels(): void
    {
        $calculator = app(CharacterStatsCalculator::class);
        $phases = $calculator->getHerosJourneyPhases();

        $this->assertCount(10, $phases);
        $this->assertEquals(1, $phases[1]['minLevel']);
        $this->assertEquals(5, $phases[1]['maxLevel']);
        $this->assertEquals(46, $phases[10]['minLevel']);
        $this->assertEquals(50, $phases[10]['maxLevel']);

        foreach ($phases as $phase) {
            $this->assertNotEmpty($phase['name']);
            $this->assertNotEmpty($phase['tagline']);
            $this->assertNotEmpty($phase['lore']);
            $this->assertNotEmpty($phase['rewards']);
        }
    }

    public function test_dashboard_renders_character_stats(): void
    {
        $user = UserModel::factory()->create([
            'career' => 'ingenieria_sistemas',
            'institution_type' => 'universidad_publica',
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->has('characterStats')
            ->has('characterStats.classTitle')
            ->has('characterStats.attributes')
        );
    }

    public function test_profile_page_renders_heros_journey_phases_and_character_stats(): void
    {
        $user = UserModel::factory()->create([
            'career' => 'medicina',
            'institution_type' => 'universidad_publica',
        ]);

        $response = $this->actingAs($user)->get(route('profile.edit'));

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Profile/Edit')
            ->has('characterStats')
            ->has('herosJourneyPhases', 10)
        );
    }
}
