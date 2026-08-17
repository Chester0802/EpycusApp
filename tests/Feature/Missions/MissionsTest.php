<?php

declare(strict_types=1);

namespace Tests\Feature\Missions;

use App\Modules\Identity\Infrastructure\Models\UserModel;
use App\Modules\Missions\Infrastructure\Models\MissionModel;
use App\Modules\Missions\Infrastructure\Models\SubtaskModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class MissionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_view_missions_page(): void
    {
        $user = UserModel::factory()->create();

        $response = $this->actingAs($user)->get(route('missions.index'));

        $response->assertStatus(200);
    }

    public function test_user_can_create_mission_with_subtasks(): void
    {
        $user = UserModel::factory()->create();

        $response = $this->actingAs($user)->post(route('missions.store'), [
            'title' => 'Entregar informe final',
            'description' => 'Revisar capítulos 1 al 4',
            'difficulty' => 'hard',
            'priority' => 'alta',
            'due_date' => now()->addDays(3)->toDateString(),
            'subtasks' => ['Capítulo 1', 'Capítulo 2'],
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('missions', [
            'user_id' => $user->id,
            'title' => 'Entregar informe final',
            'difficulty' => 'hard',
            'priority' => 'alta',
        ]);

        $mission = MissionModel::where('user_id', $user->id)->firstOrFail();
        $this->assertCount(2, $mission->subtasks);
    }

    public function test_user_can_complete_mission_and_receive_xp_and_coins(): void
    {
        $user = UserModel::factory()->create();

        $this->actingAs($user)->post(route('missions.store'), [
            'title' => 'Resolver guía de ejercicios',
            'difficulty' => 'medium',
            'priority' => 'normal',
        ]);

        $mission = MissionModel::where('user_id', $user->id)->firstOrFail();

        $response = $this->actingAs($user)->post(route('missions.complete', ['id' => $mission->id]));

        $response->assertRedirect();

        $mission->refresh();
        $this->assertNotNull($mission->completed_at);
        $this->assertEquals(30, $mission->xp_awarded);

        $this->assertDatabaseHas('user_progress', [
            'user_id' => $user->id,
            'total_xp' => 30,
            'coins' => 3,
        ]);
    }

    public function test_user_can_uncomplete_or_unarchive_mission(): void
    {
        $user = UserModel::factory()->create();

        $this->actingAs($user)->post(route('missions.store'), [
            'title' => 'Misión completada previa',
            'difficulty' => 'easy',
            'priority' => 'normal',
        ]);

        $mission = MissionModel::where('user_id', $user->id)->firstOrFail();
        $this->actingAs($user)->post(route('missions.complete', ['id' => $mission->id]));

        $this->assertNotNull($mission->fresh()->completed_at);

        $response = $this->actingAs($user)->post(route('missions.uncomplete', ['id' => $mission->id]));

        $response->assertRedirect();

        $mission->refresh();
        $this->assertNull($mission->completed_at);
        $this->assertEquals(0, $mission->xp_awarded);
    }

    public function test_user_can_toggle_subtask(): void
    {
        $user = UserModel::factory()->create();

        $this->actingAs($user)->post(route('missions.store'), [
            'title' => 'Misión con subtareas',
            'difficulty' => 'medium',
            'priority' => 'normal',
            'subtasks' => ['Paso A', 'Paso B'],
        ]);

        $mission = MissionModel::where('user_id', $user->id)->firstOrFail();
        $subtask = $mission->subtasks()->firstOrFail();

        $response = $this->actingAs($user)->postJson(route('missions.subtasks.toggle', [
            'id' => $mission->id,
            'subtaskId' => $subtask->id,
        ]));

        $response->assertStatus(200)
            ->assertJson([
                'completed' => true,
                'mission_completed' => false,
            ]);

        $subtask->refresh();
        $this->assertTrue($subtask->is_completed);
    }

    public function test_user_can_reorder_subtasks(): void
    {
        $user = UserModel::factory()->create();

        $this->actingAs($user)->post(route('missions.store'), [
            'title' => 'Misión para reordenar',
            'difficulty' => 'easy',
            'priority' => 'baja',
            'subtasks' => ['Primero', 'Segundo'],
        ]);

        $mission = MissionModel::where('user_id', $user->id)->firstOrFail();
        $subtasks = $mission->subtasks()->orderBy('id')->get();
        $firstId = $subtasks[0]->id;
        $secondId = $subtasks[1]->id;

        $response = $this->actingAs($user)->post(route('missions.subtasks.reorder', ['id' => $mission->id]), [
            'ordered_ids' => [$secondId, $firstId],
        ]);

        $response->assertRedirect();

        $this->assertEquals(0, SubtaskModel::find($secondId)->sort_order);
        $this->assertEquals(1, SubtaskModel::find($firstId)->sort_order);
    }

    public function test_user_cannot_modify_other_user_mission(): void
    {
        $user1 = UserModel::factory()->create();
        $user2 = UserModel::factory()->create();

        $this->actingAs($user1)->post(route('missions.store'), [
            'title' => 'Misión de Usuario 1',
            'difficulty' => 'easy',
            'priority' => 'normal',
            'subtasks' => ['Paso Privado'],
        ]);

        $mission = MissionModel::where('user_id', $user1->id)->firstOrFail();
        $subtask = $mission->subtasks()->firstOrFail();

        $response = $this->actingAs($user2)->post(route('missions.subtasks.toggle', [
            'id' => $mission->id,
            'subtaskId' => $subtask->id,
        ]));

        $response->assertRedirect();
        $this->assertFalse($subtask->fresh()->is_completed);
    }

    public function test_user_can_delete_mission(): void
    {
        $user = UserModel::factory()->create();

        $this->actingAs($user)->post(route('missions.store'), [
            'title' => 'Misión a borrar',
            'difficulty' => 'easy',
            'priority' => 'baja',
        ]);

        $mission = MissionModel::where('user_id', $user->id)->firstOrFail();

        $response = $this->actingAs($user)->delete(route('missions.destroy', ['id' => $mission->id]));

        $response->assertRedirect();
        $this->assertSoftDeleted('missions', ['id' => $mission->id]);
    }

    public function test_user_can_create_mission_with_eisenhower_quadrant(): void
    {
        $user = UserModel::factory()->create();

        $response = $this->actingAs($user)->post(route('missions.store'), [
            'title' => 'Estudiar para examen parcial',
            'difficulty' => 'hard',
            'priority' => 'alta',
            'eisenhower_quadrant' => 'q1',
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('missions', [
            'user_id' => $user->id,
            'title' => 'Estudiar para examen parcial',
            'eisenhower_quadrant' => 'q1',
        ]);
    }

    public function test_user_can_change_mission_quadrant(): void
    {
        $user = UserModel::factory()->create();

        $this->actingAs($user)->post(route('missions.store'), [
            'title' => 'Avance de Tesis',
            'difficulty' => 'medium',
            'priority' => 'normal',
            'eisenhower_quadrant' => 'q2',
        ]);

        $mission = MissionModel::where('user_id', $user->id)->firstOrFail();

        $response = $this->actingAs($user)->post(route('missions.quadrant', ['id' => $mission->id]), [
            'quadrant' => 'q1',
        ]);

        $response->assertRedirect();
        $this->assertEquals('q1', $mission->fresh()->eisenhower_quadrant);
    }
}
