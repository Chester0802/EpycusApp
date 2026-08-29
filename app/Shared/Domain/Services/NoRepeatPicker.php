<?php

declare(strict_types=1);

namespace App\Shared\Domain\Services;

final class NoRepeatPicker
{
    /**
     * Elige un elemento al azar de $pool que NO esté en $alreadyShown.
     * Si ya se mostraron todos, reinicia el ciclo y elige de nuevo.
     */
    public function pick(array $pool, array $alreadyShown): mixed
    {
        if (empty($pool)) {
            return null;
        }

        $remaining = array_values(array_diff($pool, $alreadyShown));
        if (empty($remaining)) {
            $remaining = array_values($pool); // se agotó el ciclo, se reinicia
        }

        return $remaining[array_rand($remaining)];
    }
}
