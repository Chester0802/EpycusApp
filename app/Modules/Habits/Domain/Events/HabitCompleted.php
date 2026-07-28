<?php

declare(strict_types=1);

namespace App\Modules\Habits\Domain\Events;

/**
 * Emitido cuando se marca un hábito como cumplido para un día concreto.
 *
 * `completedFor` (no `occurredAt`) es la clave real para quien otorgue XP por
 * esto: un hábito se puede marcar a destiempo (`isLate`), y lo que importa
 * para el tope diario y para no duplicar XP es el día del hábito, no el
 * momento del clic. Ver Gamification\Application\Listeners\AwardXpFromHabitListener,
 * que depende de `habitId` + `completedFor` para construir una clave de
 * idempotencia estable (docs/03-GAMIFICACION.md §8, "Idempotencia").
 */
final readonly class HabitCompleted
{
    public function __construct(
        public int $habitId,
        public int $userId,
        public string $completedFor,
        public bool $isLate,
        public \DateTimeImmutable $occurredAt,
    ) {}
}
