<?php

declare(strict_types=1);

namespace App\Modules\Gamification\Application\UseCases;

use App\Modules\Gamification\Application\DTOs\AwardXpResultDTO;
use App\Modules\Gamification\Domain\Contracts\GamificationRepositoryInterface;
use App\Modules\Gamification\Domain\Events\GraceDayUsed;
use App\Modules\Gamification\Domain\Events\LevelUp;
use App\Modules\Gamification\Domain\Events\PhaseUnlocked;
use App\Modules\Gamification\Domain\Events\StreakExtended;
use App\Modules\Gamification\Domain\Events\XpAwarded;
use App\Modules\Gamification\Domain\Services\LevelCalculator;
use App\Modules\Gamification\Infrastructure\Models\UserProgressModel;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Events\Dispatcher;

/**
 * Único punto de entrada para otorgar XP (docs/03-GAMIFICACION.md §8). No
 * sabe nada de hábitos, pomodoros ni ningún módulo de contenido — recibe
 * `$baseXp` y `$dailyCap` ya resueltos por quien la llama (el listener del
 * evento de dominio correspondiente), porque cada fuente tiene su propia
 * clave de config con nombres distintos (`xp.habit_completed` vs
 * `daily_caps.habits`, por ejemplo) y no vale la pena mantener acá un mapa
 * `sourceType => config key` para fuentes que ni siquiera existen todavía
 * (Pomodoro, Missions...).
 *
 * Todas las fechas de este módulo (tope diario, racha) se calculan en hora
 * de Lima (`America/Lima`), no en la zona horaria por defecto de la app
 * (`config('app.timezone')` es `UTC`). El estudio es de estudiantes
 * peruanos; "hoy" tiene que ser su hoy, no el de Greenwich — un hábito
 * marcado a las 7pm en Lima (medianoche UTC) no puede contar para "mañana".
 * `xp_transactions.created_at` queda en hora de Lima por la misma razón,
 * aunque el resto de la app guarde en UTC — es una inconsistencia
 * cosmética entre tablas, no un bug: lo que importa es que el tope diario
 * y la racha usen el mismo reloj que el estudiante.
 */
final class AwardXpUseCase
{
    public function __construct(
        private GamificationRepositoryInterface $repository,
        private LevelCalculator $levelCalculator,
        private Dispatcher $events,
    ) {}

    public function execute(
        int $userId,
        string $sourceType,
        int $sourceId,
        int $baseXp,
        int $dailyCap,
        bool $countsTowardStreak,
    ): AwardXpResultDTO {
        $progress = $this->repository->findOrCreateProgress($userId);
        $now = CarbonImmutable::now('America/Lima');

        $alreadyToday = $this->repository->countTodaysTransactions($userId, $sourceType, $now);
        $wasCapped = $alreadyToday >= $dailyCap;

        $streakDays = $this->applyStreakIfQualifying($progress, $countsTowardStreak, $now);
        $multiplier = $this->streakMultiplier($streakDays);
        $amount = $wasCapped ? 0 : (int) round($baseXp * $multiplier);

        $inserted = $this->repository->insertTransactionIfNew([
            'user_id' => $userId,
            'amount' => $amount,
            'base_amount' => $baseXp,
            'multiplier' => $multiplier,
            'source_type' => $sourceType,
            'source_id' => $sourceId,
            'was_capped' => $wasCapped,
            'created_at' => $now,
        ]);

        if (! $inserted) {
            // Ya se había otorgado XP por esta acción exacta (reintento, o
            // en Habits: apagar y volver a prender el mismo hábito el
            // mismo día — `source_id` está pensado para no distinguir eso,
            // ver AwardXpFromHabitListener). No se otorga una segunda vez;
            // el streak de arriba ya quedó resuelto de forma idempotente
            // sea cual sea la rama que tomó.
            return new AwardXpResultDTO(
                xpAwarded: 0,
                wasCapped: $wasCapped,
                newTotalXp: $progress->total_xp,
                leveledUp: false,
                newLevel: $progress->current_level,
                newPhase: $progress->current_phase,
            );
        }

        $newTotalXp = $progress->total_xp + $amount;
        $newCoins = $progress->coins + intdiv($amount, (int) config('gamification.wallet.xp_per_coin'));
        $newLevel = $this->levelCalculator->levelForTotalXp($newTotalXp);
        $newPhase = $this->levelCalculator->phaseForLevel($newLevel);
        $leveledUp = $newLevel > $progress->current_level;
        $phaseChanged = $newPhase > $progress->current_phase;

        $this->repository->updateProgress($userId, [
            'total_xp' => $newTotalXp,
            'current_level' => $newLevel,
            'current_phase' => $newPhase,
            'coins' => $newCoins,
        ]);

        $this->events->dispatch(new XpAwarded($userId, $amount, $sourceType, $wasCapped, $newTotalXp));

        if ($leveledUp) {
            $this->events->dispatch(new LevelUp($userId, $newLevel, $newPhase));
        }

        if ($phaseChanged) {
            $this->events->dispatch(new PhaseUnlocked($userId, $newPhase));
        }

        return new AwardXpResultDTO($amount, $wasCapped, $newTotalXp, $leveledUp, $newLevel, $newPhase);
    }

    /**
     * Extiende la racha si esta es la primera acción del día que cuenta
     * para ella (docs/03-GAMIFICACION.md §5). Idempotente por día: la
     * segunda acción que califica el mismo día no vuelve a sumar.
     * Devuelve el número de días de racha *después* de esta acción, que es
     * el que corresponde para calcular el multiplicador de hoy mismo.
     */
    private function applyStreakIfQualifying(UserProgressModel $progress, bool $countsTowardStreak, CarbonImmutable $now): int
    {
        if (! $countsTowardStreak) {
            return $progress->current_streak;
        }

        $today = $now->toDateString();

        if ($progress->last_activity_on === $today) {
            return $progress->current_streak;
        }

        $yesterday = $now->subDay()->toDateString();
        $continuesStreak = $progress->last_activity_on === $yesterday
            || $progress->grace_pending_since === $yesterday;

        $newStreak = $continuesStreak ? $progress->current_streak + 1 : 1;
        $newLongest = max($progress->longest_streak, $newStreak);

        $this->repository->updateProgress($progress->user_id, [
            'current_streak' => $newStreak,
            'longest_streak' => $newLongest,
            'last_activity_on' => $today,
            'grace_pending_since' => null,
        ]);

        $progress->current_streak = $newStreak;
        $progress->longest_streak = $newLongest;
        $progress->last_activity_on = $today;
        $progress->grace_pending_since = null;

        $this->events->dispatch(new StreakExtended($progress->user_id, $newStreak, $this->streakMultiplier($newStreak)));

        // GraceDayUsed se emite desde EvaluateStreaksUseCase (el cron diario),
        // nunca acá: esta función solo *redime* un día de gracia ya
        // pendiente (cuando `grace_pending_since === $yesterday`), no
        // concede uno nuevo — conceder uno exige comprobar que pasó un día
        // completo sin acción, algo que una acción reactiva del usuario no
        // puede observar por sí sola.
        return $newStreak;
    }

    private function streakMultiplier(int $streakDays): float
    {
        $bonusPerWeek = (float) config('gamification.streak.bonus_per_week');
        $bonusMax = (float) config('gamification.streak.bonus_max');

        return 1 + min($bonusMax, floor($streakDays / 7) * $bonusPerWeek);
    }
}
