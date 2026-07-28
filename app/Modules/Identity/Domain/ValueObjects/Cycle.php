<?php

declare(strict_types=1);

namespace App\Modules\Identity\Domain\ValueObjects;

final readonly class Cycle
{
    public function __construct(private int $value)
    {
        if ($value < 1 || $value > 10) {
            throw new \InvalidArgumentException("Ciclo debe estar entre 1 y 10: $value");
        }
    }

    public function value(): int
    {
        return $this->value;
    }
}
