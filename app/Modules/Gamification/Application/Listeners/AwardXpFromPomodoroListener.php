<?php

declare(strict_types=1);

namespace App\Modules\Gamification\Application\Listeners;

use App\Modules\Gamification\Application\UseCases\AwardXpUseCase;
use App\Modules\Pomodoro\Domain\Events\PomodoroCompleted;

/**
 * A diferencia de Habits, acá `source_id` es directamente el id de la
 * sesión: cada `PomodoroSessionModel` es una fila nueva por sesión real
 * (no se apaga/prende como un hábito), así que no hace falta ningún
 * esquema de codificación para la idempotencia — el id ya es estable.
 */
final class AwardXpFromPomodoroListener
{
    public function __construct(private AwardXpUseCase $awardXp) {}

    public function handle(PomodoroCompleted $event): void
    {
        $this->awardXp->execute(
            userId: $event->userId,
            sourceType: 'pomodoro',
            sourceId: $event->sessionId,
            baseXp: (int) config('gamification.xp.pomodoro_completed'),
            dailyCap: (int) config('gamification.daily_caps.pomodoros'),
            countsTowardStreak: true,
        );
    }
}
