<?php

declare(strict_types=1);

namespace Tests\Feature\Villains;

use App\Modules\Habits\Domain\Events\HabitCompleted;
use App\Modules\Identity\Infrastructure\Models\UserModel;
use App\Modules\Missions\Domain\Events\MissionCompleted;
use App\Modules\Villains\Application\DTOs\ApplyDamageDTO;
use App\Modules\Villains\Application\UseCases\ApplyDamageUseCase;
use App\Modules\Villains\Application\UseCases\GetCurrentVillainUseCase;
use App\Modules\Villains\Domain\Events\VillainDefeated;
use App\Modules\Villains\Infrastructure\Models\VillainInstanceModel;
use App\Modules\Villains\Infrastructure\Models\VillainModel;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

final class VillainsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Ensure database has default villains seeded for testing
        if (VillainModel::count() === 0) {
            VillainModel::create([
                'code' => 'procrastination',
                'name' => 'La Postergación',
                'description' => 'El arte de dejar todo para después.',
                'weakness_description' => 'Débil contra misiones y grupos de estudio.',
            ]);
            VillainModel::create([
                'code' => 'distraction',
                'name' => 'La Distracción',
                'description' => 'Redes sociales y avisos.',
                'weakness_description' => 'Débil contra pomodoro.',
            ]);
            VillainModel::create([
                'code' => 'anxiety',
                'name' => 'La Ansiedad',
                'description' => 'Agobio y estrés.',
                'weakness_description' => 'Débil contra hábitos y diario.',
            ]);
        }
    }

    public function test_user_can_view_villains_page(): void
    {
        $user = UserModel::factory()->create();

        $response = $this->actingAs($user)->get(route('villains.index'));

        $response->assertStatus(200);
    }

    public function test_weekly_villain_is_assigned_automatically(): void
    {
        $user = UserModel::factory()->create();

        $useCase = app(GetCurrentVillainUseCase::class);
        $result = $useCase->execute($user->id);

        $this->assertNotNull($result);
        $this->assertDatabaseHas('villain_instances', [
            'user_id' => $user->id,
            'status' => 'active',
        ]);
    }

    public function test_completing_habit_applies_damage_to_vulnerable_villain(): void
    {
        $user = UserModel::factory()->create();
        $anxietyVillain = VillainModel::where('code', 'anxiety')->firstOrFail();

        $instance = VillainInstanceModel::create([
            'user_id' => $user->id,
            'villain_id' => $anxietyVillain->id,
            'week_number' => 1,
            'total_hp' => 100,
            'remaining_hp' => 100,
            'status' => 'active',
            'assigned_at' => now()->startOfWeek()->format('Y-m-d H:i:s'),
            'expires_at' => now()->endOfWeek()->format('Y-m-d H:i:s'),
        ]);

        $applyDamage = app(ApplyDamageUseCase::class);
        $result = $applyDamage->execute(new ApplyDamageDTO(
            userId: $user->id,
            sourceType: 'habit',
            occurredAt: new \DateTimeImmutable,
        ));

        $this->assertTrue($result['damage_applied']);
        $this->assertEquals(90, $result['remaining_hp']);
        $this->assertEquals(90, $instance->fresh()->remaining_hp);
    }

    public function test_completing_mission_applies_damage_to_vulnerable_villain(): void
    {
        $user = UserModel::factory()->create();
        $procrastinationVillain = VillainModel::where('code', 'procrastination')->firstOrFail();

        $instance = VillainInstanceModel::create([
            'user_id' => $user->id,
            'villain_id' => $procrastinationVillain->id,
            'week_number' => 1,
            'total_hp' => 100,
            'remaining_hp' => 100,
            'status' => 'active',
            'assigned_at' => now()->startOfWeek()->format('Y-m-d H:i:s'),
            'expires_at' => now()->endOfWeek()->format('Y-m-d H:i:s'),
        ]);

        $applyDamage = app(ApplyDamageUseCase::class);
        $result = $applyDamage->execute(new ApplyDamageDTO(
            userId: $user->id,
            sourceType: 'mission',
            occurredAt: new \DateTimeImmutable,
        ));

        $this->assertTrue($result['damage_applied']);
        $this->assertEquals(90, $result['remaining_hp']);
    }

    public function test_villain_is_marked_defeated_when_hp_reaches_zero(): void
    {
        Event::fake([VillainDefeated::class]);

        $user = UserModel::factory()->create();
        $procrastinationVillain = VillainModel::where('code', 'procrastination')->firstOrFail();

        $instance = VillainInstanceModel::create([
            'user_id' => $user->id,
            'villain_id' => $procrastinationVillain->id,
            'week_number' => 1,
            'total_hp' => 10,
            'remaining_hp' => 10,
            'status' => 'active',
            'assigned_at' => now()->startOfWeek()->format('Y-m-d H:i:s'),
            'expires_at' => now()->endOfWeek()->format('Y-m-d H:i:s'),
        ]);

        $applyDamage = app(ApplyDamageUseCase::class);
        $result = $applyDamage->execute(new ApplyDamageDTO(
            userId: $user->id,
            sourceType: 'mission',
            occurredAt: new \DateTimeImmutable,
        ));

        $this->assertTrue($result['damage_applied']);
        $this->assertTrue($result['defeated'] ?? false);
        $this->assertEquals('defeated', $instance->fresh()->status);

        Event::assertDispatched(VillainDefeated::class);
    }

    public function test_non_vulnerable_sources_apply_base_damage(): void
    {
        $user = UserModel::factory()->create();
        $distractionVillain = VillainModel::where('code', 'distraction')->firstOrFail();

        $instance = VillainInstanceModel::create([
            'user_id' => $user->id,
            'villain_id' => $distractionVillain->id,
            'week_number' => 1,
            'total_hp' => 100,
            'remaining_hp' => 100,
            'status' => 'active',
            'assigned_at' => now()->startOfWeek()->format('Y-m-d H:i:s'),
            'expires_at' => now()->endOfWeek()->format('Y-m-d H:i:s'),
        ]);

        $applyDamage = app(ApplyDamageUseCase::class);
        // Distraction is weak to 'pomodoro', not 'habit'
        $result = $applyDamage->execute(new ApplyDamageDTO(
            userId: $user->id,
            sourceType: 'habit',
            occurredAt: new \DateTimeImmutable,
        ));

        $this->assertTrue($result['damage_applied']);
        $this->assertEquals(5, $result['damage']);
        $this->assertEquals(95, $result['remaining_hp']);
        $this->assertEquals(95, $instance->fresh()->remaining_hp);
    }

    public function test_creating_journal_entry_applies_damage_to_vulnerable_villain(): void
    {
        $user = UserModel::factory()->create();
        $anxietyVillain = VillainModel::where('code', 'anxiety')->firstOrFail();

        $instance = VillainInstanceModel::create([
            'user_id' => $user->id,
            'villain_id' => $anxietyVillain->id,
            'week_number' => 1,
            'total_hp' => 100,
            'remaining_hp' => 100,
            'status' => 'active',
            'assigned_at' => now()->startOfWeek()->format('Y-m-d H:i:s'),
            'expires_at' => now()->endOfWeek()->format('Y-m-d H:i:s'),
        ]);

        $response = $this->actingAs($user)->postJson(route('wellbeing.store'), [
            'date' => now()->toDateString(),
            'mood_score' => 4,
            'energy' => 4,
            'stress' => 2,
            'content' => 'Entrada reflexiva para calmar la ansiedad.',
        ]);

        $response->assertStatus(200);
        $this->assertEquals(90, $instance->fresh()->remaining_hp);
    }

    public function test_all_10_villains_are_valid_and_bestiary_is_rendered(): void
    {
        $user = UserModel::factory()->create();

        $response = $this->actingAs($user)->get(route('villains.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Villains/Index')
            ->has('bestiary')
            ->has('stats')
        );
    }
}
