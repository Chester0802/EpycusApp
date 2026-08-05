<?php

declare(strict_types=1);

namespace Tests\Feature\Calendar;

use App\Modules\Identity\Infrastructure\Models\UserModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ClassScheduleTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_and_view_class_schedule(): void
    {
        $user = UserModel::factory()->create();

        $response = $this->actingAs($user)->post('/calendar/schedules', [
            'course_name' => 'Matemática Discreta',
            'day_of_week' => 1,
            'start_time' => '08:00',
            'end_time' => '10:00',
            'classroom' => 'Aula 101',
            'color' => 'primary',
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('class_schedules', [
            'user_id' => $user->id,
            'course_name' => 'Matemática Discreta',
            'day_of_week' => 1,
            'classroom' => 'Aula 101',
        ]);
    }

    public function test_user_can_delete_own_class_schedule(): void
    {
        $user = UserModel::factory()->create();

        $this->actingAs($user)->post('/calendar/schedules', [
            'course_name' => 'Física General',
            'day_of_week' => 2,
            'start_time' => '10:00',
            'end_time' => '12:00',
            'color' => 'accent',
        ]);

        $schedule = \DB::table('class_schedules')->where('user_id', $user->id)->first();

        $deleteResponse = $this->actingAs($user)->delete("/calendar/schedules/{$schedule->id}");

        $deleteResponse->assertRedirect();
        $this->assertDatabaseMissing('class_schedules', [
            'id' => $schedule->id,
        ]);
    }
}
