<?php

declare(strict_types=1);

namespace App\Modules\Gamification\Application\Listeners;

use App\Modules\Gamification\Application\UseCases\AwardXpUseCase;
use App\Modules\Habits\Domain\Events\HabitCompleted;
use Carbon\Carbon;

/**
 * Traduce el evento de dominio de Habits al vocabulario de Gamification
 * (base XP, tope diario, clave de idempotencia) — Habits no sabe nada de
 * esto, y AwardXpUseCase no sabe nada de hábitos (ver comentario en esa
 * clase). Este listener es el único lugar que conoce ambos vocabularios.
 *
 * Registrado en GamificationServiceProvider::boot(), no en un
 * EventServiceProvider central — este proyecto no tiene uno, cada módulo
 * que necesita escuchar algo de otro se suscribe en su propio provider.
 */
final class AwardXpFromHabitListener
{
    public function __construct(private AwardXpUseCase $awardXp) {}

    public function handle(HabitCompleted $event): void
    {
        // `source_id` codifica (habit_id, completed_for) en un solo entero
        // para que la unicidad de xp_transactions (user_id, source_type,
        // source_id) bloquee exactamente "una vez por hábito por día" — ni
        // más (evita farmear XP apagando y prendiendo el mismo hábito el
        // mismo día: `habit_completions` borra y recrea la fila con un id
        // nuevo cada vez que se vuelve a marcar, así que el id de esa fila
        // NO sirve como clave estable) ni menos (marcar el mismo hábito en
        // días distintos sí debe otorgar XP cada vez).
        $sourceId = $event->habitId * 100_000_000 + (int) Carbon::parse($event->completedFor)->format('Ymd');

        $this->awardXp->execute(
            userId: $event->userId,
            sourceType: 'habit',
            sourceId: $sourceId,
            baseXp: (int) config('gamification.xp.habit_completed'),
            dailyCap: (int) config('gamification.daily_caps.habits'),
            countsTowardStreak: true,
        );
    }
}
