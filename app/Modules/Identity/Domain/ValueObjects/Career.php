<?php

declare(strict_types=1);

namespace App\Modules\Identity\Domain\ValueObjects;

final readonly class Career
{
    private const VALID = [
        'Medicina', 'Enfermería', 'Obstetricia',
        'Administración de Empresas', 'Contabilidad',
        'Ingeniería Civil', 'Ingeniería Industrial', 'Ingeniería de Minas', 'Arquitectura',
        'Ingeniería de Sistemas',
        'Derecho',
    ];

    public function __construct(private string $value)
    {
        if (! in_array($value, self::VALID, true)) {
            throw new \InvalidArgumentException("Carrera no válida: $value");
        }
    }

    public function value(): string
    {
        return $this->value;
    }

    public function avatarStyle(): string
    {
        return match (true) {
            in_array($this->value, ['Medicina', 'Enfermería', 'Obstetricia'], true) => 'health',
            in_array($this->value, ['Administración de Empresas', 'Contabilidad'], true) => 'business',
            in_array($this->value, ['Ingeniería Civil', 'Ingeniería Industrial', 'Ingeniería de Minas', 'Arquitectura'], true) => 'technical',
            $this->value === 'Ingeniería de Sistemas' => 'systems',
            $this->value === 'Derecho' => 'law',
            default => 'technical',
        };
    }
}
