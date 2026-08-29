<?php

declare(strict_types=1);

namespace App\Modules\Identity\Domain\ValueObjects;

final readonly class InstitutionType
{
    private const VALID = ['universidad', 'instituto'];

    public function __construct(private string $value)
    {
        if (! in_array($value, self::VALID, true)) {
            throw new \InvalidArgumentException("Tipo de institución no válido: $value");
        }
    }

    public function value(): string
    {
        return $this->value;
    }
}
