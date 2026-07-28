<?php

declare(strict_types=1);

namespace App\Shared\Domain\Services;

final class NoRepeatPicker
{
    public function pick(array $pool, array $alreadyShown): mixed
    {
        $remaining = array_diff($pool, $alreadyShown);

        if ($remaining === []) {
            $remaining = $pool;
        }

        return $remaining[array_rand($remaining)];
    }
}
