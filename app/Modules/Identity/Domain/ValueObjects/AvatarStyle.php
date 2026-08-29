<?php

declare(strict_types=1);

namespace App\Modules\Identity\Domain\ValueObjects;

final readonly class AvatarStyle
{
    private const VALID = ['health', 'business', 'technical', 'systems', 'law'];

    public function __construct(private string $value)
    {
        if (! in_array($value, self::VALID, true)) {
            throw new \InvalidArgumentException("Estilo de avatar no válido: $value");
        }
    }

    public function value(): string
    {
        return $this->value;
    }
}
