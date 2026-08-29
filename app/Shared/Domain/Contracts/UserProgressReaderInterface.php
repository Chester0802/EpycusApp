<?php

declare(strict_types=1);

namespace App\Shared\Domain\Contracts;

interface UserProgressReaderInterface
{
    public function getLevelFor(int $userId): int;

    public function getPhaseFor(int $userId): int;

    public function getTotalXpFor(int $userId): int;

    public function getCurrentStreakFor(int $userId): int;

    /**
     * Saldo de monedas (docs/03-GAMIFICACION.md §9). No estaba en la
     * versión original de esta interfaz pese a que `UserWallet` ya
     * figuraba en la lista de entidades de docs/01-MODULOS.md §6 — se
     * agregó al implementarla por primera vez (Fase 4).
     */
    public function getCoinsFor(int $userId): int;
}
