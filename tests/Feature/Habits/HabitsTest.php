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
            'icon' => '📖',
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('habits', [
            'user_id' => $user->id,
            'title' => 'Estudiar 30 min',
            'category' => 'estudio',
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
}
