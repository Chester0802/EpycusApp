<?php

declare(strict_types=1);

namespace App\Modules\Pomodoro\Domain\Contracts;

use App\Modules\Pomodoro\Infrastructure\Models\PomodoroSessionModel;
use Illuminate\Support\Collection;

interface PomodoroRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): PomodoroSessionModel;

    public function findActiveForUser(int $userId): ?PomodoroSessionModel;

    public function findByIdAndUser(int $id, int $userId): ?PomodoroSessionModel;

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(PomodoroSessionModel $session, array $data): PomodoroSessionModel;

    /**
     * Sesiones de hoy para el bloque "Sesiones de hoy" del historial
     * integrado (docs/01-MODULOS.md §3).
     *
     * @return Collection<int, PomodoroSessionModel>
     */
    public function todaysSessionsForUser(int $userId): Collection;

    /**
     * @return Collection<int, PomodoroSessionModel>
     */
    public function sessionsSinceForUser(int $userId, \DateTimeImmutable $since): Collection;
}
