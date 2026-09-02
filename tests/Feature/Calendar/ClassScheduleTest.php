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
            'name'      => 'Matemática Discreta',
            'color'     => 'primary',
            'professor' => 'Dr. Alan Turing',
            'credits'   => 4,
            'sessions'  => [
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
            'user_id'   => $user->id,
            'name'      => 'Matemática Discreta',
            'professor' => 'Dr. Alan Turing',
            'credits'   => 4,
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
            'name'      => 'Física I',
            'color'     => 'accent',
            'credits'   => 3,
            'sessions'  => [
                ['day_of_week' => 2, 'start_time' => '10:00', 'end_time' => '12:00'],
            ],
        ]);

        $course = \DB::table('courses')->where('user_id', $user->id)->first();

        $updateResponse = $this->actingAs($user)->put("/calendar/courses/{$course->id}", [
            'name'      => 'Física Avanzada',
            'color'     => 'success',
            'professor' => 'Dra. Marie Curie',
            'credits'   => 5,
            'sessions'  => [
                ['day_of_week' => 4, 'start_time' => '14:00', 'end_time' => '16:00', 'classroom' => 'Aula 202'],
            ],
        ]);

        $updateResponse->assertRedirect();

        $this->assertDatabaseHas('courses', [
            'id'        => $course->id,
            'name'      => 'Física Avanzada',
            'professor' => 'Dra. Marie Curie',
            'credits'   => 5,
        ]);
    }

    public function test_user_can_create_course_with_ends_at_only_and_relaxed_time_format(): void
    {
        $user = UserModel::factory()->create();

        $response = $this->actingAs($user)->post('/calendar/courses', [
            'name'      => 'Química Orgánica',
            'color'     => 'success',
            'ends_at'   => '2026-12-20',
            'sessions'  => [
                [
                    'day_of_week' => 2,
                    'start_time'  => '8:30',
                    'end_time'    => '10:15:00',
                    'classroom'   => 'Lab Q-1',
                ],
            ],
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('courses', [
            'user_id'   => $user->id,
            'name'      => 'Química Orgánica',
            'ends_at'   => '2026-12-20 00:00:00',
        ]);

        $course = \DB::table('courses')->where('user_id', $user->id)->first();

        $this->assertDatabaseHas('course_sessions', [
            'course_id'   => $course->id,
            'day_of_week' => 2,
            'start_time'  => '08:30',
            'end_time'    => '10:15',
        ]);
    }

    public function test_courses_index_and_show_eager_load_sessions(): void
    {
        $user = UserModel::factory()->create();

        $this->actingAs($user)->post('/calendar/courses', [
            'name'      => 'Algoritmos y Estructuras',
            'color'     => 'accent',
            'sessions'  => [
                ['day_of_week' => 1, 'start_time' => '09:00', 'end_time' => '11:00', 'classroom' => 'A-201'],
            ],
        ]);

        $course = \App\Modules\Calendar\Infrastructure\Models\CourseModel::where('user_id', $user->id)->first();

        $indexResponse = $this->actingAs($user)->get(route('courses.index'));
        $indexResponse->assertOk();
        $indexCourses = $indexResponse->original->getData()['page']['props']['courses'];
        $this->assertNotEmpty($indexCourses[0]['sessions']);

        $showResponse = $this->actingAs($user)->get(route('courses.show', $course->id));
        $showResponse->assertOk();
        $showCourse = $showResponse->original->getData()['page']['props']['course'];
        $this->assertNotEmpty($showCourse['sessions']);
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
