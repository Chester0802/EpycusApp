<?php

declare(strict_types=1);

namespace App\Shared\Domain\Services;

/**
 * Resuelve **un solo** personaje decorativo por módulo. Documentado a fondo
 * en docs/04-DISENO-VISUAL.md ("Avatares — bloque 1"); leer ahí antes de
 * tocar esto o de agregar assets nuevos.
 *
 * Rediseño de 2026-07-28 (corrección directa del usuario tras ver la
 * primera versión, que mostraba 4 imágenes juntas en el Dashboard — no le
 * gustó): ahora **cada módulo tiene un dígito de orden fijo** y muestra una
 * sola imagen, elegida al azar entre **cualquier fase disponible** para ese
 * orden — no se sigue la fase real de progreso del usuario (`current_phase`)
 * para esto, es puramente decorativo. Mapeo pedido por el usuario:
 * Dashboard=1, Hábitos=2, Misiones=3, Pomodoro=4 (en Pomodoro el personaje
 * sale sentado estudiando — pose a propósito para ese módulo).
 *
 * Nombre de archivo: `{Prefijo}_{Fem|Masc}_{fase}{orden}.png`. `Base` no
 * depende de la carrera (fase 1, cualquier estilo); desde fase 2 cada
 * estilo de carrera (`Career::avatarStyle()`) tiene su propio prefijo real
 * — ver `STYLE_PREFIXES`. Se arma la lista de candidatos con `file_exists`,
 * no con una lista fija, así que agregar más fases/estilos después no
 * necesita tocar este archivo.
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
     * Orden de archivo (segundo dígito) fijo por módulo — pedido explícito
     * del usuario. `missions` está listado para cuando ese módulo exista
     * (Fase 6); nada lo usa todavía.
     *
     * @var array<string, int>
     */
    public const MODULE_ORDER = [
        'dashboard' => 1,
        'habits' => 2,
        'missions' => 3,
        'pomodoro' => 4,
    ];

    public function imageForModule(?string $avatarStyle, ?string $avatarGender, string $module): ?string
    {
        $order = self::MODULE_ORDER[$module] ?? 1;
        $gender = $avatarGender === 'f' ? 'Fem' : 'Masc';

        $prefixes = array_unique(array_values(array_filter([
            $avatarStyle !== null ? (self::STYLE_PREFIXES[$avatarStyle] ?? null) : null,
            'Base',
        ])));

        $candidates = [];

        foreach ($prefixes as $prefix) {
            for ($phase = 1; $phase <= 9; $phase++) {
                $filename = "{$prefix}_{$gender}_{$phase}{$order}.png";

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
