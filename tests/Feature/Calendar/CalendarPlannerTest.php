<?php

declare(strict_types=1);

namespace Tests\Feature\Calendar;

use App\Modules\DayPlanner\Infrastructure\Models\DailyPlanItemModel;
use App\Modules\DayPlanner\Infrastructure\Models\DailyRoutineModel;
use App\Modules\Identity\Infrastructure\Models\UserModel;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class CalendarPlannerTest extends TestCase
{
    use RefreshDatabase;

    public function test_calendar_page_renders_with_integrated_plan(): void
    {
        $user = UserModel::factory()->create();

        $response = $this->actingAs($user)->get(route('calendar.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Calendar/Index')
            ->has('plan')
            ->has('courses')
            ->has('todayDate')
        );
    }

    public function test_user_can_create_and_edit_plan_item(): void
    {
        $user = UserModel::factory()->create();
        $today = Carbon::now('America/Lima')->toDateString();

        // 1. Crear actividad
        $createRes = $this->actingAs($user)->post(route('calendar.planner.items.store'), [
            'plan_date' => $today,
            'title' => 'Repasar Álgebra Lineal',
            'category' => 'estudio',
            'time_block' => 'morning',
            'scheduled_time' => '08:30',
            'estimated_minutes' => 45,
        ]);

        $createRes->assertRedirect();

        $item = DailyPlanItemModel::where('user_id', $user->id)
            ->where('title', 'Repasar Álgebra Lineal')
            ->firstOrFail();

        $this->assertEquals('08:30', $item->scheduled_time);
        $this->assertEquals(45, $item->estimated_minutes);

        // 2. Editar actividad (título y hora)
        $updateRes = $this->actingAs($user)->put(route('calendar.planner.items.update', ['id' => $item->id]), [
            'title' => 'Repasar Álgebra Lineal y Matrices',
            'category' => 'estudio',
            'time_block' => 'afternoon',
            'scheduled_time' => '14:00',
            'estimated_minutes' => 60,
        ]);

        $updateRes->assertRedirect();

        $item->refresh();
        $this->assertEquals('Repasar Álgebra Lineal y Matrices', $item->title);
        $this->assertEquals('14:00', $item->scheduled_time);
        $this->assertEquals('afternoon', $item->time_block);
        $this->assertEquals(60, $item->estimated_minutes);
    }

    public function test_user_can_update_plan_item_status_done_and_skipped(): void
    {
        $user = UserModel::factory()->create();
        $today = Carbon::now('America/Lima')->toDateString();

        $item = DailyPlanItemModel::create([
            'user_id' => $user->id,
            'plan_date' => $today,
            'title' => 'Lectura de paper',
            'category' => 'estudio',
            'time_block' => 'morning',
            'status' => 'pending',
        ]);

        // Marcar como Hecho
        $doneRes = $this->actingAs($user)->patchJson(route('calendar.planner.items.status', ['id' => $item->id]), [
            'status' => 'done',
        ]);

        $doneRes->assertStatus(200);
        $doneRes->assertJson(['status' => 'done']);

        // Marcar como No Hecho con motivo
        $skipRes = $this->actingAs($user)->patchJson(route('calendar.planner.items.status', ['id' => $item->id]), [
            'status' => 'skipped',
            'skip_reason' => 'sin_tiempo',
        ]);

        $skipRes->assertStatus(200);
        $item->refresh();
        $this->assertEquals('skipped', $item->status);
        $this->assertEquals('sin_tiempo', $item->skip_reason);
    }

    public function test_user_can_create_and_edit_routine_template(): void
    {
        $user = UserModel::factory()->create();

        // 1. Crear plantilla
        $createRes = $this->actingAs($user)->post(route('calendar.planner.routines.store'), [
            'title' => 'Meditación matutina',
            'category' => 'salud',
            'time_block' => 'morning',
            'scheduled_time' => '06:45',
            'estimated_minutes' => 15,
            'days_of_week' => [1, 2, 3, 4, 5],
        ]);

        $createRes->assertRedirect();

        $routine = DailyRoutineModel::where('user_id', $user->id)
            ->where('title', 'Meditación matutina')
            ->firstOrFail();

        $this->assertEquals('06:45', $routine->scheduled_time);

        // 2. Modificar plantilla (hora y título)
        $updateRes = $this->actingAs($user)->put(route('calendar.planner.routines.update', ['id' => $routine->id]), [
            'title' => 'Mindfulness y respiración',
            'category' => 'salud',
            'time_block' => 'morning',
            'scheduled_time' => '07:00',
            'estimated_minutes' => 20,
            'days_of_week' => [1, 2, 3, 4, 5, 6, 7],
        ]);

        $updateRes->assertRedirect();

        $routine->refresh();
        $this->assertEquals('Mindfulness y respiración', $routine->title);
        $this->assertEquals('07:00', $routine->scheduled_time);
        $this->assertEquals(20, $routine->estimated_minutes);
    }
}
