<?php

declare(strict_types=1);

namespace Tests\Feature\Calendar;

use App\Modules\Identity\Infrastructure\Models\UserModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ClassScheduleTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_and_view_course_with_sessions(): void
    {
        $user = UserModel::factory()->create();

        $response = $this->actingAs($user)->post('/calendar/courses', [
            'name'  => 'Matemática Discreta',
            'color' => 'primary',
            'sessions' => [
                [
                    'day_of_week' => 1,
                    'start_time'  => '08:00',
                    'end_time'    => '10:00',
                    'classroom'   => 'Aula 101',
                ],
                [
                    'day_of_week' => 3,
                    'start_time'  => '14:00',
                    'end_time'    => '16:00',
                    'classroom'   => 'Lab B',
                ],
            ],
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('courses', [
            'user_id' => $user->id,
            'name'    => 'Matemática Discreta',
        ]);

        $course = \DB::table('courses')->where('user_id', $user->id)->first();

        $this->assertDatabaseHas('course_sessions', [
            'course_id'   => $course->id,
            'day_of_week' => 1,
            'classroom'   => 'Aula 101',
        ]);

        $this->assertDatabaseHas('course_sessions', [
            'course_id'   => $course->id,
            'day_of_week' => 3,
            'classroom'   => 'Lab B',
        ]);
    }

    public function test_user_can_update_course(): void
    {
        $user = UserModel::factory()->create();

        $this->actingAs($user)->post('/calendar/courses', [
            'name'  => 'Física I',
            'color' => 'accent',
            'sessions' => [
                ['day_of_week' => 2, 'start_time' => '10:00', 'end_time' => '12:00'],
            ],
        ]);

        $course = \DB::table('courses')->where('user_id', $user->id)->first();

        $updateResponse = $this->actingAs($user)->put("/calendar/courses/{$course->id}", [
            'name'  => 'Física Avanzada',
            'color' => 'success',
            'sessions' => [
                ['day_of_week' => 4, 'start_time' => '14:00', 'end_time' => '16:00', 'classroom' => 'Aula 202'],
            ],
        ]);

        $updateResponse->assertRedirect();

        $this->assertDatabaseHas('courses', [
            'id'   => $course->id,
            'name' => 'Física Avanzada',
        ]);
    }

    public function test_user_can_delete_own_course(): void
    {
        $user = UserModel::factory()->create();

        $this->actingAs($user)->post('/calendar/courses', [
            'name'  => 'Física General',
            'color' => 'accent',
            'sessions' => [
                ['day_of_week' => 2, 'start_time' => '10:00', 'end_time' => '12:00'],
            ],
        ]);

        $course = \DB::table('courses')->where('user_id', $user->id)->first();

        $deleteResponse = $this->actingAs($user)->delete("/calendar/courses/{$course->id}");

        $deleteResponse->assertRedirect();
        $this->assertDatabaseMissing('courses', [
            'id' => $course->id,
        ]);
    }
}
