<?php

declare(strict_types=1);

namespace Tests\Feature\Habits;

use App\Modules\Identity\Infrastructure\Models\UserModel;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class HabitsTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_view_habits_page(): void
    {
        $user = UserModel::factory()->create();

        $response = $this->actingAs($user)->get(route('habits.index'));

        $response->assertStatus(200);
    }

    public function test_user_can_create_a_habit(): void
    {
        $user = UserModel::factory()->create();

        $response = $this->actingAs($user)->post(route('habits.store'), [
            'title' => 'Estudiar 30 min',
            'category' => 'estudio',
            'frequency' => ['type' => 'daily'],
            'icon' => 'book-open',
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('habits', [
            'user_id' => $user->id,
            'title' => 'Estudiar 30 min',
            'category' => 'estudio',
            'icon' => 'book-open',
        ]);
    }

    public function test_user_can_update_a_habit(): void
    {
        $user = UserModel::factory()->create();

        $this->actingAs($user)->post(route('habits.store'), [
            'title' => 'Hábito Inicial',
            'category' => 'otro',
            'frequency' => ['type' => 'daily'],
            'icon' => 'zap',
        ]);

        $habit = $user->habits()->firstOrFail();

        $response = $this->actingAs($user)->patch(route('habits.update', ['id' => $habit->id]), [
            'title' => 'Hábito Modificado',
            'category' => 'ejercicio',
            'frequency' => ['type' => 'daily'],
            'icon' => 'dumbbell',
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('habits', [
            'id' => $habit->id,
            'title' => 'Hábito Modificado',
            'category' => 'ejercicio',
            'icon' => 'dumbbell',
        ]);
    }

    public function test_user_can_toggle_habit_completion(): void
    {
        $user = UserModel::factory()->create();

        $this->actingAs($user)->post(route('habits.store'), [
            'title' => 'Dormir 8 horas',
            'category' => 'sueno',
            'frequency' => ['type' => 'daily'],
        ]);

        $habit = $user->habits()->firstOrFail();

        $response = $this->actingAs($user)->postJson(route('habits.toggle', ['id' => $habit->id]), [
            'date' => now()->toDateString(),
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'completed' => true,
                'xp_awarded' => 10,
            ]);

        $this->assertDatabaseHas('habit_completions', [
            'habit_id' => $habit->id,
            'user_id' => $user->id,
            'completed_for' => now()->toDateString(),
        ]);

        $this->assertDatabaseHas('user_progress', [
            'user_id' => $user->id,
            'total_xp' => 10,
            'coins' => 1,
        ]);
    }

    public function test_user_can_archive_a_habit(): void
    {
        $user = UserModel::factory()->create();

        $this->actingAs($user)->post(route('habits.store'), [
            'title' => 'Hábito para archivar',
            'category' => 'otro',
            'frequency' => ['type' => 'daily'],
        ]);

        $habit = $user->habits()->firstOrFail();

        $response = $this->actingAs($user)->patch(route('habits.archive', ['id' => $habit->id]));

        $response->assertRedirect();
        $this->assertFalse($habit->fresh()->is_active);
    }

    public function test_user_can_unarchive_a_habit(): void
    {
        $user = UserModel::factory()->create();

        $this->actingAs($user)->post(route('habits.store'), [
            'title' => 'Hábito archivado previa',
            'category' => 'otro',
            'frequency' => ['type' => 'daily'],
        ]);

        $habit = $user->habits()->firstOrFail();
        $this->actingAs($user)->patch(route('habits.archive', ['id' => $habit->id]));
        $this->assertFalse($habit->fresh()->is_active);

        $response = $this->actingAs($user)->patch(route('habits.unarchive', ['id' => $habit->id]));

        $response->assertRedirect();
        $this->assertTrue($habit->fresh()->is_active);
    }

    public function test_user_can_delete_habit(): void
    {
        $user = UserModel::factory()->create();

        $this->actingAs($user)->post(route('habits.store'), [
            'title' => 'Hacer ejercicio',
            'category' => 'ejercicio',
            'frequency' => ['type' => 'daily'],
        ]);

        $habit = $user->habits()->firstOrFail();

        $response = $this->actingAs($user)->delete(route('habits.destroy', ['id' => $habit->id]));

        $response->assertRedirect();

        $this->assertSoftDeleted('habits', [
            'id' => $habit->id,
        ]);
    }

    public function test_user_cannot_modify_other_user_habit(): void
    {
        $user1 = UserModel::factory()->create();
        $user2 = UserModel::factory()->create();

        $this->actingAs($user1)->post(route('habits.store'), [
            'title' => 'Hábito Privado de User 1',
            'category' => 'estudio',
            'frequency' => ['type' => 'daily'],
        ]);

        $habit = $user1->habits()->firstOrFail();

        $response = $this->actingAs($user2)->patch(route('habits.update', ['id' => $habit->id]), [
            'title' => 'Intento Hack',
            'category' => 'estudio',
            'frequency' => ['type' => 'daily'],
        ]);

        $response->assertRedirect();
        $this->assertEquals('Hábito Privado de User 1', $habit->fresh()->title);
    }

    public function test_user_can_create_habit_with_time_of_day_and_cue_trigger(): void
    {
        $user = UserModel::factory()->create();

        $response = $this->actingAs($user)->post(route('habits.store'), [
            'title' => 'Repaso de 20 min',
            'category' => 'estudio',
            'frequency' => ['type' => 'daily'],
            'icon' => 'book-open',
            'time_of_day' => 'morning',
            'cue_trigger' => 'Después de tomar café en la mañana',
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('habits', [
            'user_id' => $user->id,
            'title' => 'Repaso de 20 min',
            'time_of_day' => 'morning',
            'cue_trigger' => 'Después de tomar café en la mañana',
        ]);
    }
}
