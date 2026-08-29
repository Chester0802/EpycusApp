<?php

declare(strict_types=1);

namespace App\Modules\Gamification\Infrastructure\Repositories;

use App\Modules\Gamification\Infrastructure\Models\UserProgressModel;
use App\Shared\Domain\Contracts\UserProgressReaderInterface;

/**
 * Implementación del contrato de lectura declarado en
 * `Shared/Domain/Contracts/UserProgressReaderInterface.php` (ya existía,
 * sin implementación, desde antes de que este módulo se construyera —
 * ver docs/01-MODULOS.md §6). Es la única forma en que otro módulo
 * (Habits hoy, Ranking/Personalization después) puede leer progreso sin
 * depender de las clases internas de Gamification.
 *
 * Usuarios sin fila en `user_progress` (nunca completaron nada que otorgue
 * XP) devuelven los valores iniciales, no un error — todavía no existen
 * como "progreso", pero preguntar por su nivel/fase/racha es una pregunta
 * válida en cualquier momento.
 */
final class EloquentUserProgressReader implements UserProgressReaderInterface
{
    public function getLevelFor(int $userId): int
    {
        $progress = $this->find($userId);

        return $progress !== null ? $progress->current_level : 1;
    }

    public function getPhaseFor(int $userId): int
    {
        $progress = $this->find($userId);

        return $progress !== null ? $progress->current_phase : 1;
    }

    public function getTotalXpFor(int $userId): int
    {
        $progress = $this->find($userId);

        return $progress !== null ? $progress->total_xp : 0;
    }

    public function getCurrentStreakFor(int $userId): int
    {
        $progress = $this->find($userId);

        return $progress !== null ? $progress->current_streak : 0;
    }

    public function getCoinsFor(int $userId): int
    {
        $progress = $this->find($userId);

        return $progress !== null ? $progress->coins : 0;
    }

    private function find(int $userId): ?UserProgressModel
    {
        return UserProgressModel::query()->find($userId);
    }
}
