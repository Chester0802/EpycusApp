<?php

declare(strict_types=1);

namespace App\Modules\Identity\Domain\ValueObjects;

final readonly class Career
{
    /**
     * Única fuente de verdad de la lista cerrada de carreras (decisión D-16).
     * `config/careers.php` lee de aquí — nunca al revés — para que no existan
     * dos listas que se puedan desincronizar.
     *
     * @var array<string, list<string>>
     */
    private const CAREERS_BY_STYLE = [
        'health' => ['Medicina', 'Enfermería', 'Obstetricia'],
        'business' => ['Administración de Empresas', 'Contabilidad'],
        'technical' => ['Ingeniería Civil', 'Ingeniería Industrial', 'Ingeniería de Minas', 'Arquitectura'],
        'systems' => ['Ingeniería de Sistemas'],
        'law' => ['Derecho'],
    ];

    public function __construct(private string $value)
    {
        if (! in_array($value, self::all(), true)) {
            throw new \InvalidArgumentException("Carrera no válida: $value");
        }
    }

    public function value(): string
    {
        return $this->value;
    }

    public function avatarStyle(): string
    {
        foreach (self::CAREERS_BY_STYLE as $style => $careers) {
            if (in_array($this->value, $careers, true)) {
                return $style;
            }
        }

        // Inalcanzable: el constructor ya validó que $value está en CAREERS_BY_STYLE.
        throw new \LogicException("Carrera sin estilo de avatar mapeado: {$this->value}");
    }

    /** @return list<string> */
    public static function all(): array
    {
        return array_merge(...array_values(self::CAREERS_BY_STYLE));
    }

    /** @return array<string, list<string>> */
    public static function groupedByStyle(): array
    {
        return self::CAREERS_BY_STYLE;
    }
}
