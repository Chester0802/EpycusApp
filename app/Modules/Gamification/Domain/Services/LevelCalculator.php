<?php

declare(strict_types=1);

namespace App\Modules\Gamification\Domain\Services;

/**
 * Curva de niveles de docs/03-GAMIFICACION.md §4:
 * `XP_para_subir_al_nivel(n) = base + (n - 1) * increment`.
 *
 * Nota para quien lea esto después: la tabla "XP acumulado" del propio
 * documento (§4) no reproduce exactamente esta fórmula sumada término a
 * término (para nivel 6 la suma real da 950, la tabla dice 1.000; para
 * nivel 11 da 3.025, la tabla dice 2.925) — son valores aproximados para
 * que el documento sea legible, no un oráculo exacto. La fórmula de la
 * fila "XP para ese nivel" sí es exacta y es la que se implementa acá;
 * no fuerces este código a encajar con la columna "XP acumulado".
 */
final class LevelCalculator
{
    public function __construct(
        private readonly int $baseXp,
        private readonly int $increment,
        private readonly int $maxLevel,
        private readonly int $levelsPerPhase,
    ) {}

    /**
     * XP que hace falta acumular *desde* `$level` para llegar a `$level + 1`.
     */
    public function xpNeededToAdvanceFromLevel(int $level): int
    {
        return $this->baseXp + ($level - 1) * $this->increment;
    }

    /**
     * Nivel correspondiente a un total de XP acumulado, recorriendo la
     * curva término a término (nunca más de `maxLevel` iteraciones).
     */
    public function levelForTotalXp(int $totalXp): int
    {
        $level = 1;
        $remaining = $totalXp;

        while ($level < $this->maxLevel) {
            $needed = $this->xpNeededToAdvanceFromLevel($level);

            if ($remaining < $needed) {
                break;
            }

            $remaining -= $needed;
            $level++;
        }

        return $level;
    }

    /**
     * Total de XP acumulado necesario para alcanzar el nivel `$level`.
     */
    public function xpForLevel(int $level): int
    {
        if ($level <= 1) {
            return 0;
        }

        $total = 0;
        for ($i = 1; $i < $level && $i <= $this->maxLevel; $i++) {
            $total += $this->xpNeededToAdvanceFromLevel($i);
        }

        return $total;
    }

    /**
     * `fase = floor((nivel - 1) / levelsPerPhase) + 1` (docs/03-GAMIFICACION.md §8).
     */
    public function phaseForLevel(int $level): int
    {
        return intdiv($level - 1, $this->levelsPerPhase) + 1;
    }
}
