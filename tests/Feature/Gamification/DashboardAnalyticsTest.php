<?php

declare(strict_types=1);

namespace Tests\Feature\Gamification;

use App\Modules\Gamification\Domain\Services\DashboardAnalyticsService;
use App\Modules\Identity\Infrastructure\Models\UserModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

final class DashboardAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    public function test_analytics_service_returns_all_five_chart_datasets(): void
    {
        $user = UserModel::factory()->create([
            'career' => 'medicina',
            'institution_type' => 'universidad_publica',
        ]);

        // 1. Insertar sesión Pomodoro
        DB::table('pomodoro_sessions')->insert([
            'user_id' => $user->id,
            'planned_minutes' => 25,
            'focus_minutes' => 25,
            'status' => 'completed',
            'started_at' => now()->setTime(10, 0),
            'ended_at' => now()->setTime(10, 25),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 2. Insertar entrada de diario
        DB::table('journal_entries')->insert([
            'user_id' => $user->id,
            'date' => now()->toDateString(),
            'mood_score' => 4,
            'energy' => 5,
            'stress' => 2,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $service = app(DashboardAnalyticsService::class);
        $analytics = $service->getAnalytics($user->id);

        $this->assertArrayHasKey('heatmap', $analytics);
        $this->assertArrayHasKey('courseDistribution', $analytics);
        $this->assertArrayHasKey('peakHours', $analytics);
        $this->assertArrayHasKey('wellbeingTrend', $analytics);
        $this->assertArrayHasKey('villainHistory', $analytics);

        $this->assertCount(60, $analytics['heatmap']);
        $this->assertCount(14, $analytics['wellbeingTrend']);
        $this->assertArrayHasKey('peakWindow', $analytics['peakHours']);
    }

    public function test_dashboard_renders_with_analytics_props(): void
    {
        $user = UserModel::factory()->create([
            'career' => 'medicina',
            'institution_type' => 'universidad_publica',
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->has('analytics')
            ->has('analytics.heatmap')
            ->has('analytics.courseDistribution')
            ->has('analytics.peakHours')
            ->has('analytics.wellbeingTrend')
            ->has('analytics.villainHistory')
        );
    }

    public function test_course_distribution_calculates_exact_study_time_per_course(): void
    {
        $user = UserModel::factory()->create();

        $courseA = DB::table('courses')->insertGetId([
            'user_id' => $user->id,
            'name' => 'Física Cuántica',
            'color' => '#8b5cf6',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $courseB = DB::table('courses')->insertGetId([
            'user_id' => $user->id,
            'name' => 'Química Orgánica',
            'color' => '#10b981',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $missionA = DB::table('missions')->insertGetId([
            'user_id' => $user->id,
            'course_id' => $courseA,
            'title' => 'Problemas de Schrodinger',
            'difficulty' => 'hard',
            'priority' => 'alta',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 2 pomodoros de 25 min para Misión A (50 min Física Cuántica)
        DB::table('pomodoro_sessions')->insert([
            [
                'user_id' => $user->id,
                'mission_id' => $missionA,
                'planned_minutes' => 25,
                'focus_minutes' => 25,
                'status' => 'completed',
                'started_at' => now()->subHours(2),
                'ended_at' => now()->subHours(2)->addMinutes(25),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'user_id' => $user->id,
                'mission_id' => $missionA,
                'planned_minutes' => 25,
                'focus_minutes' => 25,
                'status' => 'completed',
                'started_at' => now()->subHour(),
                'ended_at' => now()->subHour()->addMinutes(25),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $service = app(DashboardAnalyticsService::class);
        $distribution = $service->getCourseDistribution($user->id);

        $this->assertCount(2, $distribution);
        $physics = collect($distribution)->firstWhere('name', 'Física Cuántica');
        $chemistry = collect($distribution)->firstWhere('name', 'Química Orgánica');

        $this->assertEquals(50, $physics['minutes']);
        $this->assertEquals(2, $physics['sessions']);
        $this->assertEquals(100, $physics['percentage']);

        $this->assertEquals(0, $chemistry['minutes']);
        $this->assertEquals(0, $chemistry['sessions']);
    }
}
