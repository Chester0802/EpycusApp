<?php

declare(strict_types=1);

namespace App\Modules\Gamification\Domain\Contracts;

use App\Modules\Gamification\Infrastructure\Models\UserProgressModel;
use Illuminate\Support\Collection;

interface GamificationRepositoryInterface
{
    /**
     * Crea la fila de progreso en su primer uso (equivalente a un
     * `firstOrCreate`) — no se crea al registrar porque Gamification no
     * escucha `UserRegistered`; nace con la primera acción que otorga XP.
     */
    public function findOrCreateProgress(int $userId): UserProgressModel;

    /**
     * Cuenta cuántas transacciones de este `sourceType` ya se registraron
     * hoy para el usuario (tope diario, docs/03-GAMIFICACION.md §3). "Hoy"
     * se mide por `created_at`, el momento real del otorgamiento — no por
     * la fecha a la que pertenece la acción (`completed_for` puede ser un
     * día pasado si se marcó a destiempo).
     */
    public function countTodaysTransactions(int $userId, string $sourceType, \DateTimeImmutable $today): int;

    /**
     * Intenta insertar la transacción de XP. Devuelve `false` sin lanzar
     * nada si `(user_id, source_type, source_id)` ya existía — ese es el
     * mecanismo entero de idempotencia (docs/03-GAMIFICACION.md §8).
     *
     * @param  array<string, mixed>  $data
     */
    public function insertTransactionIfNew(array $data): bool;

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateProgress(int $userId, array $data): void;

    /**
     * Perfiles con racha activa cuyo último día de actividad quedó antes de
     * `yesterday` — candidatos a evaluar en `EvaluateStreaksUseCase`.
     *
     * @return Collection<int, UserProgressModel>
     */
    public function progressWithPossibleStreakGap(\DateTimeImmutable $yesterday): Collection;
}
