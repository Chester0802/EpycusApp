<?php

declare(strict_types=1);

namespace Tests\Feature\Gamification;

use App\Modules\Gamification\Application\UseCases\AwardXpUseCase;
use App\Modules\Gamification\Application\UseCases\EvaluateStreaksUseCase;
use App\Modules\Identity\Infrastructure\Models\UserModel;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Reproduce, día por día, el ejemplo literal de docs/03-GAMIFICACION.md §5:
 * racha de 20 días → falla el día 21 (gracia disponible) → sigue en 20,
 * gasta 1 gracia → el día 22 decide si se redime (pasa a 21) o se rompe.
 */
final class StreakTest extends TestCase
{
    use RefreshDatabase;

    private const TZ = 'America/Lima';

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    public function test_streak_grows_one_day_at_a_time_with_daily_qualifying_actions(): void
    {
        $user = UserModel::factory()->create();
        $useCase = app(AwardXpUseCase::class);

        $start = CarbonImmutable::parse('2026-09-07 10:00:00', self::TZ);

        for ($day = 0; $day < 5; $day++) {
            CarbonImmutable::setTestNow($start->addDays($day));
            $useCase->execute($user->id, 'habit', 5000 + $day, 10, 5, true);
        }

        $this->assertDatabaseHas('user_progress', [
            'user_id' => $user->id,
            'current_streak' => 5,
            'longest_streak' => 5,
        ]);
    }

    public function test_missing_one_day_with_grace_available_freezes_the_streak_instead_of_breaking_it(): void
    {
        [$user, $day20] = $this->userWithStreakOf20Days();

        // Día 21: no hace nada. El cron corre al empezar el día 22 y evalúa "ayer" (día 21).
        CarbonImmutable::setTestNow($day20->addDays(2)->setTime(0, 10));
        app(EvaluateStreaksUseCase::class)->execute();

        $this->assertDatabaseHas('user_progress', [
            'user_id' => $user->id,
            'current_streak' => 20, // "sigue en 20" — no se rompe, tampoco sube
            'grace_days_left' => 2, // gastó 1 de los 3 disponibles
        ]);
    }

    public function test_completing_a_habit_the_day_after_a_grace_extends_the_streak(): void
    {
        [$user, $day20] = $this->userWithStreakOf20Days();
        $useCase = app(AwardXpUseCase::class);

        // Día 21: sin acción. Cron corre al empezar el día 22 (evalúa día 21).
        CarbonImmutable::setTestNow($day20->addDays(2)->setTime(0, 10));
        app(EvaluateStreaksUseCase::class)->execute();

        // Día 22: cumple.
        CarbonImmutable::setTestNow($day20->addDays(2)->setTime(10, 0));
        $useCase->execute($user->id, 'habit', 999_999, 10, 5, true);

        // "Cumple el día 22 → la racha pasa a 21" (docs/03-GAMIFICACION.md §5).
        $this->assertDatabaseHas('user_progress', [
            'user_id' => $user->id,
            'current_streak' => 21,
        ]);
    }

    public function test_missing_a_second_consecutive_day_after_a_grace_breaks_the_streak(): void
    {
        [$user, $day20] = $this->userWithStreakOf20Days();

        // Día 21: sin acción. Cron al empezar el día 22 (evalúa día 21) — gasta gracia.
        CarbonImmutable::setTestNow($day20->addDays(2)->setTime(0, 10));
        app(EvaluateStreaksUseCase::class)->execute();

        // Día 22: TAMPOCO hace nada. Cron al empezar el día 23 (evalúa día 22) — se rompe.
        CarbonImmutable::setTestNow($day20->addDays(3)->setTime(0, 10));
        app(EvaluateStreaksUseCase::class)->execute();

        $this->assertDatabaseHas('user_progress', [
            'user_id' => $user->id,
            'current_streak' => 0,
        ]);
    }

    public function test_running_the_daily_command_twice_the_same_day_does_not_double_spend_grace(): void
    {
        [$user, $day20] = $this->userWithStreakOf20Days();

        CarbonImmutable::setTestNow($day20->addDays(2)->setTime(0, 10));
        app(EvaluateStreaksUseCase::class)->execute();
        app(EvaluateStreaksUseCase::class)->execute(); // corrida repetida, mismo día

        $this->assertDatabaseHas('user_progress', [
            'user_id' => $user->id,
            'current_streak' => 20,
            'grace_days_left' => 2, // no 1 — la segunda corrida no debe gastar otra
        ]);
    }

    /**
     * @return array{0: UserModel, 1: CarbonImmutable} usuario con racha de
     *                                                 20 días consecutivos, con "hoy" congelado en el día 20 al salir.
     */
    private function userWithStreakOf20Days(): array
    {
        $user = UserModel::factory()->create();
        $useCase = app(AwardXpUseCase::class);
        $start = CarbonImmutable::parse('2026-09-07 10:00:00', self::TZ);

        for ($day = 0; $day < 20; $day++) {
            CarbonImmutable::setTestNow($start->addDays($day));
            $useCase->execute($user->id, 'habit', 6000 + $day, 10, 5, true);
        }

        $this->assertDatabaseHas('user_progress', ['user_id' => $user->id, 'current_streak' => 20]);

        // Día 20 real (start = día 1; el índice 19 del bucle es el día 20).
        return [$user, $start->addDays(19)];
    }
}
