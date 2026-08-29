<?php

declare(strict_types=1);

namespace Tests\Feature\Gamification;

use App\Modules\Gamification\Application\UseCases\AwardXpUseCase;
use App\Modules\Identity\Infrastructure\Models\UserModel;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class AwardXpTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    public function test_completing_a_habit_awards_the_configured_xp_and_creates_progress(): void
    {
        $user = UserModel::factory()->create();

        $this->completeNewHabit($user, 'Estudiar');

        $this->assertDatabaseHas('user_progress', [
            'user_id' => $user->id,
            'total_xp' => 10,
            'current_level' => 1,
            'current_phase' => 1,
            'current_streak' => 1,
        ]);
    }

    public function test_toggling_a_habit_off_and_on_the_same_day_does_not_award_xp_twice(): void
    {
        // Este es exactamente el hueco que motivó codificar source_id como
        // habit_id + fecha en vez de usar el id autoincremental de
        // habit_completions (ver AwardXpFromHabitListener): apagar y
        // prender de nuevo el mismo hábito el mismo día borra y recrea la
        // fila de completado con un id nuevo, así que si se usara ese id
        // como clave de idempotencia, este test fallaría (otorgaría 20 en
        // vez de 10).
        $user = UserModel::factory()->create();
        $habitId = $this->createHabit($user, 'Dormir 8 horas');

        $this->toggle($user, $habitId); // completa
        $this->toggle($user, $habitId); // descompleta
        $this->toggle($user, $habitId); // vuelve a completar, mismo día

        $this->assertDatabaseHas('user_progress', [
            'user_id' => $user->id,
            'total_xp' => 10,
        ]);

        $this->assertDatabaseCount('xp_transactions', 1);
    }

    public function test_sixth_distinct_habit_completed_same_day_is_capped(): void
    {
        $user = UserModel::factory()->create();

        for ($i = 1; $i <= 6; $i++) {
            $this->completeNewHabit($user, "Hábito {$i}");
        }

        // Tope de docs/03-GAMIFICACION.md §3: 5 hábitos con XP por día, 50 XP tope.
        $this->assertDatabaseHas('user_progress', [
            'user_id' => $user->id,
            'total_xp' => 50,
        ]);

        $capped = DB::table('xp_transactions')
            ->where('user_id', $user->id)
            ->where('was_capped', true)
            ->count();

        $this->assertSame(1, $capped);
    }

    public function test_uncompleting_a_habit_never_revokes_previously_awarded_xp(): void
    {
        // docs/03-GAMIFICACION.md §10: "Qué NO implementar — Pérdida de XP
        // o de nivel". Completar dos hábitos DISTINTOS y descompletar el
        // segundo no debe bajar el total por debajo de lo ya otorgado.
        $user = UserModel::factory()->create();

        $this->completeNewHabit($user, 'Hábito A');
        $secondHabitId = $this->createHabit($user, 'Hábito B');
        $this->toggle($user, $secondHabitId); // completa B
        $this->toggle($user, $secondHabitId); // descompleta B

        $this->assertDatabaseHas('user_progress', [
            'user_id' => $user->id,
            'total_xp' => 20,
        ]);
    }

    public function test_awarding_xp_directly_is_idempotent_for_the_same_source(): void
    {
        $user = UserModel::factory()->create();
        $useCase = app(AwardXpUseCase::class);

        $first = $useCase->execute(
            userId: $user->id,
            sourceType: 'habit',
            sourceId: 999,
            baseXp: 10,
            dailyCap: 5,
            countsTowardStreak: true,
        );
        $second = $useCase->execute(
            userId: $user->id,
            sourceType: 'habit',
            sourceId: 999,
            baseXp: 10,
            dailyCap: 5,
            countsTowardStreak: true,
        );

        $this->assertSame(10, $first->xpAwarded);
        $this->assertSame(0, $second->xpAwarded);
        $this->assertDatabaseHas('user_progress', ['user_id' => $user->id, 'total_xp' => 10]);
    }

    public function test_level_and_phase_cross_at_the_exact_formula_threshold(): void
    {
        // Fórmula de docs/03-GAMIFICACION.md §4: XP_para_subir(n) = 100 + (n-1)*45.
        // Acumulado exacto para llegar a nivel 6 (niveles 1..5): 950.
        // (La columna "XP acumulado" del propio documento es aproximada —
        // ver el comentario de LevelCalculator — así que el test verifica
        // contra la fórmula, no contra esa tabla.)
        $user = UserModel::factory()->create();
        $useCase = app(AwardXpUseCase::class);

        $useCase->execute($user->id, 'manual', 1, 949, 100, false);
        $this->assertDatabaseHas('user_progress', ['user_id' => $user->id, 'current_level' => 5, 'current_phase' => 1]);

        $useCase->execute($user->id, 'manual', 2, 1, 100, false);
        $this->assertDatabaseHas('user_progress', ['user_id' => $user->id, 'current_level' => 6, 'current_phase' => 2]);
    }

    private function createHabit(UserModel $user, string $title): int
    {
        $this->actingAs($user)->post(route('habits.store'), [
            'title' => $title,
            'category' => 'estudio',
            'frequency' => ['type' => 'daily'],
        ]);

        return (int) $user->habits()->where('title', $title)->firstOrFail()->id;
    }

    private function toggle(UserModel $user, int $habitId): void
    {
        $this->actingAs($user)->postJson(route('habits.toggle', ['id' => $habitId]), [
            'date' => now()->toDateString(),
        ])->assertStatus(200);
    }

    private function completeNewHabit(UserModel $user, string $title): void
    {
        $this->toggle($user, $this->createHabit($user, $title));
    }
}
