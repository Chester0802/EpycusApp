<?php

declare(strict_types=1);

namespace App\Shared\Domain\Services;

/**
 * Resuelve **un solo** personaje decorativo por módulo. Documentado a fondo
 * en docs/15-CATALOGO-IMAGENES.md (convención de nombres completa) y en
 * docs/04-DISENO-VISUAL.md ("Avatares — bloque 1"); leer ahí antes de tocar
 * esto o de agregar assets nuevos.
 *
 * Nombre de archivo: `{Prefijo}_{Fem|Masc}_{Fase}{Posición}.png`. `Base` no
 * depende de la carrera (fase 1, cualquier estilo, "aspecto de
 * estudiante"); desde fase 2 cada estilo de carrera (`Career::avatarStyle()`)
 * tiene su propio prefijo real — ver `STYLE_PREFIXES`.
 *
 * **Posición (segundo dígito) — corregido 2026-07-29, terminología exacta
 * confirmada por el usuario.** No es un "orden de módulo" arbitrario, es la
 * **pose del personaje**, igual para cualquier carrera/fase/género:
 * 1=parado normal, 2=parado saludando, 3=sentado, 4=sentado usando laptop.
 * `MODULE_POSITION` fija qué pose le corresponde a cada módulo (Pomodoro=4
 * porque "sentado con laptop" es la pose de estudio; Dashboard=1 como
 * neutral) — la fase sí se sortea al azar entre las disponibles para esa
 * posición, eso es lo único decorativo/aleatorio de acá, **no** la posición.
 * El comportamiento no cambió con esta corrección, solo el nombre: el bucle
 * de abajo ya variaba la fase con la posición fija desde 2026-07-28, que es
 * exactamente lo que este significado correcto pide.
 *
 * Se arma la lista de candidatos con `file_exists`, no con una lista fija,
 * así que agregar más fases/estilos después no necesita tocar este archivo.
 */
final class AvatarAssetResolver
{
    /**
     * Estilo de `Career::avatarStyle()` → prefijo real de archivo. Solo se
     * listan los estilos que ya tienen arte propio (bloque 1); `business`,
     * `systems` y `law` todavía no tienen prefijo — usan solo `Base`.
     *
     * @var array<string, string>
     */
    private const STYLE_PREFIXES = [
        'health' => 'Medicina',
        'technical' => 'Tecnico',
    ];

    /**
     * Pose (segundo dígito del nombre de archivo) fija por módulo — pedido
     * explícito del usuario. `missions` está listado para cuando ese módulo
     * exista (ver docs/13-ROADMAP.md); nada lo usa todavía. Ver
     * docs/15-CATALOGO-IMAGENES.md para el significado de cada valor 1-4.
     *
     * @var array<string, int>
     */
    public const MODULE_POSITION = [
        'dashboard' => 1,
        'habits' => 2,
        'missions' => 3,
        'pomodoro' => 4,
    ];

    public function imageForModule(?string $avatarStyle, ?string $avatarGender, string $module): ?string
    {
        $position = self::MODULE_POSITION[$module] ?? 1;
        $gender = $avatarGender === 'f' ? 'Fem' : 'Masc';

        $prefixes = array_unique(array_values(array_filter([
            $avatarStyle !== null ? (self::STYLE_PREFIXES[$avatarStyle] ?? null) : null,
            'Base',
        ])));

        $candidates = [];

        foreach ($prefixes as $prefix) {
            for ($phase = 1; $phase <= 9; $phase++) {
                $filename = "{$prefix}_{$gender}_{$phase}{$position}.png";

                if (file_exists(public_path("assets/avatars/{$filename}"))) {
                    $candidates[] = "/assets/avatars/{$filename}";
                }
            }
        }

        if ($candidates === []) {
            return null;
        }

        return $candidates[array_rand($candidates)];
    }
}
