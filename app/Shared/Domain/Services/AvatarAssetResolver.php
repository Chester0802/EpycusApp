<?php

declare(strict_types=1);

namespace App\Shared\Domain\Services;

/**
 * Resuelve qué imágenes de avatar mostrar para un usuario. Documentado a
 * fondo en docs/04-DISENO-VISUAL.md ("Avatares — bloque 1"); leer ahí antes
 * de tocar esto o de agregar assets nuevos.
 *
 * Nombre de archivo: `{Prefijo}_{Fem|Masc}_{fase}{orden}.png`. El primer
 * dígito después del género es la **fase** del avatar (`current_phase`,
 * 1-10 — NO `current_level`, que va de 1 a 50: son conceptos distintos en
 * este esquema y el usuario que entregó las imágenes las llamó "nivel"
 * coloquialmente, pero encajan con fase). El segundo dígito es el orden de
 * presentación (1-4, hasta 4 variantes por fase/género/estilo).
 *
 * `Base` es género-específico pero **no depende de la carrera** — se usa
 * para la fase 1 de cualquier estilo. A partir de la fase 2 cada estilo de
 * carrera (`Career::avatarStyle()`) tiene su propio set. Todavía no existen
 * assets para todos los estilos ni para todas las fases — por eso se
 * verifica con `file_exists` en vez de asumir, y se cae a la fase
 * disponible más alta por debajo de la actual, y en último caso a `Base`.
 */
final class AvatarAssetResolver
{
    /**
     * Estilo de `Career::avatarStyle()` → prefijo real de archivo. Solo se
     * listan los estilos que ya tienen arte propio (bloque 1, 2026-07-28);
     * `business`, `systems` y `law` todavía no tienen prefijo — caen a
     * `Base` hasta que lleguen sus imágenes.
     *
     * @var array<string, string>
     */
    private const STYLE_PREFIXES = [
        'health' => 'Medicina',
        'technical' => 'Tecnico',
    ];

    /**
     * @return list<string> rutas públicas (0 a 4), ya en orden aleatorio
     */
    public function imagesFor(?string $avatarStyle, ?string $avatarGender, int $phase): array
    {
        $gender = $avatarGender === 'f' ? 'Fem' : 'Masc';
        $prefix = $avatarStyle !== null ? (self::STYLE_PREFIXES[$avatarStyle] ?? null) : null;

        if ($prefix !== null) {
            for ($p = min($phase, 9); $p >= 2; $p--) {
                $paths = $this->existingPathsFor($prefix, $gender, $p);

                if ($paths !== []) {
                    shuffle($paths);

                    return $paths;
                }
            }
        }

        $paths = $this->existingPathsFor('Base', $gender, 1);
        shuffle($paths);

        return $paths;
    }

    /**
     * @return list<string>
     */
    private function existingPathsFor(string $prefix, string $gender, int $phase): array
    {
        $paths = [];

        for ($order = 1; $order <= 4; $order++) {
            $filename = "{$prefix}_{$gender}_{$phase}{$order}.png";

            if (file_exists(public_path("assets/avatars/{$filename}"))) {
                $paths[] = "/assets/avatars/{$filename}";
            }
        }

        return $paths;
    }
}
