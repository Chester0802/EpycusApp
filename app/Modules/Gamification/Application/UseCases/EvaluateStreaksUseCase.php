<?php

declare(strict_types=1);

namespace App\Modules\Gamification\Application\UseCases;

use App\Modules\Gamification\Domain\Contracts\GamificationRepositoryInterface;
use App\Modules\Gamification\Domain\Events\GraceDayUsed;
use App\Modules\Gamification\Domain\Events\StreakBroken;
use App\Modules\Gamification\Infrastructure\Models\UserProgressModel;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Events\Dispatcher;

/**
 * Corre una vez al día (comando `gamification:evaluate-streaks`,
 * `routes/console.php`), evaluando qué pasó *ayer* para cada racha activa
 * (docs/03-GAMIFICACION.md §5). No puede ser puramente reactivo a la
 * próxima acción del usuario: si alguien no vuelve a abrir la app nunca
 * más, su racha debe quedar rota igual, no "congelada" para siempre en el
 * último valor.
 *
 * Ejemplo completo verificado (ver tests/Feature/Gamification/StreakTest.php),
 * tomado literal de docs/03-GAMIFICACION.md §5:
 * racha de 20 días → falla el día 21 (con gracia disponible) → sigue en 20,
 * gasta 1 gracia → cumple el día 22 → pasa a 21. O, si falla el día 22
 * también → se rompe, `grace_used: true`.
 *
 * Es IMPRESCINDIBLE que sea idempotente: si el cron se corre dos veces el
 * mismo día (o se atrasa y corre tarde), no debe romper una racha que ya
 * había recibido su día de gracia en la corrida anterior del mismo día.
 * Por eso la comparación no es solo "¿hay hueco?" sino "¿el hueco es más
 * viejo que la gracia que ya se concedió?" — ver `grace_pending_since` en
 * la migración de `user_progress`.
 */
final class EvaluateStreaksUseCase
{
    public function __construct(
        private GamificationRepositoryInterface $repository,
        private Dispatcher $events,
    ) {}

    public function execute(): void
    {
        // Hora de Lima, no UTC — ver el comentario equivalente en
        // AwardXpUseCase. El comando ya se agenda a las 00:10 Lima
        // (routes/console.php), pero `now()` acá también tiene que estar
        // en Lima para que "ayer" sea el día que el estudiante vivió.
        $now = CarbonImmutable::now('America/Lima');
        $yesterday = $now->subDay()->toDateString();

        $candidates = $this->repository->progressWithPossibleStreakGap($now->subDay());

        foreach ($candidates as $progress) {
            $this->evaluateOne($progress, $yesterday, $now);
        }
    }

    private function evaluateOne(UserProgressModel $progress, string $yesterday, CarbonImmutable $now): void
    {
        // Actuó ayer (o ya hoy, por algún desfase de reloj) — sin hueco.
        if ($progress->last_activity_on !== null && $progress->last_activity_on >= $yesterday) {
            return;
        }

        // Ya se le había concedido gracia para este mismo hueco en una
        // corrida anterior del mismo día — no volver a evaluarlo (idempotencia).
        if ($progress->grace_pending_since !== null && $progress->grace_pending_since >= $yesterday) {
            return;
        }

        // Había una gracia pendiente de una corrida ANTERIOR (para un día
        // más viejo que "ayer") y no se redimió con una acción real — se
        // rompe la racha, con grace_used:true.
        if ($progress->grace_pending_since !== null) {
            $this->breakStreak($progress, graceUsed: true);

            return;
        }

        $graceDaysLeft = $this->resetGraceMonthIfNeeded($progress, $now);

        if ($graceDaysLeft > 0) {
            $this->repository->updateProgress($progress->user_id, [
                'grace_days_left' => $graceDaysLeft - 1,
                'grace_month' => $now->format('Y-m'),
                'grace_pending_since' => $yesterday,
            ]);

            $this->events->dispatch(new GraceDayUsed($progress->user_id, $graceDaysLeft - 1));

            return;
        }

        $this->breakStreak($progress, graceUsed: false);
    }

    private function breakStreak(UserProgressModel $progress, bool $graceUsed): void
    {
        $previousDays = $progress->current_streak;

        $this->repository->updateProgress($progress->user_id, [
            'current_streak' => 0,
            'grace_pending_since' => null,
        ]);

        $this->events->dispatch(new StreakBroken($progress->user_id, $previousDays, $graceUsed));
    }

    /**
     * 3 días de gracia por mes calendario, no acumulables entre meses
     * (docs/03-GAMIFICACION.md §5). Si `grace_month` no es el mes actual,
     * el saldo disponible es el tope completo, no el que quedaba guardado.
     */
    private function resetGraceMonthIfNeeded(UserProgressModel $progress, CarbonImmutable $now): int
    {
        $currentMonth = $now->format('Y-m');

        if ($progress->grace_month === $currentMonth) {
            return $progress->grace_days_left;
        }

        return (int) config('gamification.streak.grace_days_per_month');
    }
}
