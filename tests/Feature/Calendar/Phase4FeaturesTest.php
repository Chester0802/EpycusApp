<?php

declare(strict_types=1);

namespace Tests\Feature\Calendar;

use App\Modules\Calendar\Infrastructure\Models\PersonalEventModel;
use App\Modules\Habits\Infrastructure\Models\HabitModel;
use App\Modules\Identity\Infrastructure\Models\UserModel;
use App\Modules\Missions\Infrastructure\Models\MissionModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class Phase4FeaturesTest extends TestCase
{
    use RefreshDatabase;

    private UserModel $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = UserModel::factory()->create();
    }

    public function test_user_can_create_and_manage_break_habits(): void
    {
        $this->actingAs($this->user);

        $response = $this->post(route('habits.store'), [
            'title' => 'No fumar cigarrillos',
            'category' => 'otro',
            'habit_type' => 'break',
            'frequency' => ['type' => 'daily'],
            'time_of_day' => 'anytime',
        ]);

        $response->assertRedirect();

        $habit = HabitModel::where('user_id', $this->user->id)->first();
        $this->assertNotNull($habit);
        $this->assertSame('break', $habit->habit_type);
        $this->assertSame('No fumar cigarrillos', $habit->title);
    }

    public function test_user_can_create_update_and_delete_personal_events(): void
    {
        $this->actingAs($this->user);

        // 1. Create personal event
        $createRes = $this->postJson(route('calendar.personal-events.store'), [
            'title' => 'Cumpleaños de Mamá',
            'type' => 'birthday',
            'event_date' => '2026-09-15',
            'start_time' => '19:00',
            'end_time' => '22:00',
            'description' => 'Cena familiar en restaurante',
            'color' => 'accent',
        ]);

        $createRes->assertStatus(201);
        $eventId = $createRes->json('event.id');

        $this->assertDatabaseHas('personal_events', [
            'id' => $eventId,
            'user_id' => $this->user->id,
            'title' => 'Cumpleaños de Mamá',
            'type' => 'birthday',
        ]);

        // 2. Update personal event
        $updateRes = $this->putJson(route('calendar.personal-events.update', ['id' => $eventId]), [
            'title' => 'Cumpleaños de Mamá - Sorpresa',
            'type' => 'birthday',
            'event_date' => '2026-09-15',
            'start_time' => '19:30',
            'end_time' => '23:00',
            'color' => 'accent',
        ]);

        $updateRes->assertOk();
        $this->assertDatabaseHas('personal_events', [
            'id' => $eventId,
            'title' => 'Cumpleaños de Mamá - Sorpresa',
        ]);

        // 3. Delete personal event
        $deleteRes = $this->deleteJson(route('calendar.personal-events.destroy', ['id' => $eventId]));
        $deleteRes->assertOk();

        $this->assertDatabaseMissing('personal_events', ['id' => $eventId]);
    }

    public function test_user_can_schedule_mission_into_daily_planner(): void
    {
        $this->actingAs($this->user);

        $mission = MissionModel::create([
            'user_id' => $this->user->id,
            'title' => 'Entregar reporte de física',
            'difficulty' => 'medium',
            'priority' => 'alta',
            'mission_type' => 'academic',
            'eisenhower_quadrant' => 'q1',
        ]);

        $response = $this->post(route('calendar.planner.items.store'), [
            'plan_date' => '2026-08-31',
            'title' => $mission->title,
            'category' => 'academic',
            'time_block' => 'morning',
            'scheduled_time' => '09:00',
            'estimated_minutes' => 60,
            'linked_mission_id' => $mission->id,
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('daily_plan_items', [
            'user_id' => $this->user->id,
            'title' => 'Entregar reporte de física',
            'linked_mission_id' => $mission->id,
            'time_block' => 'morning',
        ]);
    }
}
