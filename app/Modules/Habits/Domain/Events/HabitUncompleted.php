<?php

declare(strict_types=1);

namespace App\Modules\Habits\Domain\Events;

/**
 * Emitido al deshacer el marcado de un hábito. A propósito, nada escucha
 * este evento para tocar XP: docs/03-GAMIFICACION.md §10 prohíbe la pérdida
 * de XP o de nivel. El XP ya otorgado por `HabitCompleted` no se revierte
 * nunca, así que este evento hoy solo sirve para telemetría/analítica de
 * "marcado accidental" (docs/02-TELEMETRIA.md, evento `habit.uncompleted`).
 */
final readonly class HabitUncompleted
{
    public function __construct(
        public int $habitId,
        public int $userId,
        public string $completedFor,
        public \DateTimeImmutable $occurredAt,
    ) {}
}
