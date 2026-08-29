<?php

declare(strict_types=1);

namespace App\Modules\Identity\Domain\ValueObjects;

final readonly class SurfaceMode
{
    private const VALID = ['neumorphism', 'glass'];

    public function __construct(private string $value)
    {
        if (! in_array($value, self::VALID, true)) {
            throw new \InvalidArgumentException("Modo de superficie no válido: $value");
        }
    }

    public function value(): string
    {
        return $this->value;
    }

    public static function default(): self
    {
        return new self('neumorphism');
    }
}
