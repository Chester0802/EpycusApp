<?php

declare(strict_types=1);

namespace Tests\Feature\DayPlanner;

use App\Modules\DayPlanner\Infrastructure\Models\DailyPlanItemModel;
use App\Modules\DayPlanner\Infrastructure\Models\DailyRoutineModel;
use App\Modules\Identity\Infrastructure\Models\UserModel;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class DayPlannerTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_view_day_planner_page_and_it_auto_seeds_defaults(): void
    {
        $user = UserModel::factory()->create();

        $response = $this->actingAs($user)->get(route('day-planner.index'));

        $response->assertStatus(200);

        // Se deben haber creado rutinas y los ítems del día
        $this->assertDatabaseHas('daily_routines', [
            'user_id' => $user->id,
            'time_block' => 'morning',
        ]);

        $this->assertDatabaseHas('daily_plan_items', [
            'user_id' => $user->id,
            'plan_date' => Carbon::now('America/Lima')->toDateString(),
        ]);
    }

    public function test_user_can_mark_item_as_done_and_earn_xp(): void
    {
        $user = UserModel::factory()->create();
        $today = Carbon::now('America/Lima')->toDateString();

        $item = DailyPlanItemModel::create([
            'user_id' => $user->id,
            'plan_date' => $today,
            'title' => 'Ejercicio matutino 20 min',
            'time_block' => 'morning',
            'category' => 'salud',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($user)->patchJson(route('day-planner.items.status', ['id' => $item->id]), [
            'status' => 'done',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'done',
        ]);

        $this->assertDatabaseHas('daily_plan_items', [
            'id' => $item->id,
            'status' => 'done',
        ]);

        // Verificar que se haya otorgado XP en user_progress
        $this->assertDatabaseHas('user_progress', [
            'user_id' => $user->id,
        ]);
    }

    public function test_user_can_skip_item_with_reason(): void
    {
        $user = UserModel::factory()->create();
        $today = Carbon::now('America/Lima')->toDateString();

        $item = DailyPlanItemModel::create([
            'user_id' => $user->id,
            'plan_date' => $today,
            'title' => 'Leer noticias',
            'time_block' => 'morning',
            'category' => 'general',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($user)->patchJson(route('day-planner.items.status', ['id' => $item->id]), [
            'status' => 'skipped',
            'skip_reason' => 'cansancio',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'skipped',
        ]);

        $this->assertDatabaseHas('daily_plan_items', [
            'id' => $item->id,
            'status' => 'skipped',
            'skip_reason' => 'cansancio',
        ]);
    }

    public function test_user_can_postpone_item_to_next_block(): void
    {
        $user = UserModel::factory()->create();
        $today = Carbon::now('America/Lima')->toDateString();

        $item = DailyPlanItemModel::create([
            'user_id' => $user->id,
            'plan_date' => $today,
            'title' => 'Avanzar informe de física',
            'time_block' => 'morning',
            'category' => 'estudio',
            'status' => 'pending',
            'postponed_count' => 0,
        ]);

        $response = $this->actingAs($user)->patchJson(route('day-planner.items.status', ['id' => $item->id]), [
            'status' => 'postponed',
            'postpone_to_block' => 'afternoon',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'postponed',
            'time_block' => 'afternoon',
            'postponed_count' => 1,
        ]);

        $this->assertDatabaseHas('daily_plan_items', [
            'id' => $item->id,
            'status' => 'postponed',
            'time_block' => 'afternoon',
            'postponed_count' => 1,
        ]);
    }

    public function test_user_can_create_custom_plan_item(): void
    {
        $user = UserModel::factory()->create();
        $today = Carbon::now('America/Lima')->toDateString();

        $response = $this->actingAs($user)->post(route('day-planner.items.store'), [
            'plan_date' => $today,
            'title' => 'Reunión de grupo de cálculo',
            'category' => 'estudio',
            'time_block' => 'afternoon',
            'scheduled_time' => '16:00',
            'estimated_minutes' => 60,
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('daily_plan_items', [
            'user_id' => $user->id,
            'title' => 'Reunión de grupo de cálculo',
            'time_block' => 'afternoon',
            'scheduled_time' => '16:00',
        ]);
    }
}
