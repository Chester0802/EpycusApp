<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Catálogo de Fondos de Pantalla (Modo Vidrio)
    |--------------------------------------------------------------------------
    |
    | Catálogo oficial de fondos disponibles para la superficie Vidrio.
    | 'atardecer' es el fondo por defecto (gratuito). Cada fondo adicional
    | cuesta 50 monedas acumuladas por el usuario al ganar XP.
    |
    */
    'default_wallpaper' => 'atardecer',
    'unlock_cost' => 50,

    'items' => [
        'atardecer' => [
            'key' => 'atardecer',
            'name' => 'Atardecer cálido',
            'file' => 'atardecer.avif',
            'cost' => 0,
            'description' => 'Fondo por defecto de la aplicación.',
        ],
        'chica_anime' => [
            'key' => 'chica_anime',
            'name' => 'Chica Anime',
            'file' => 'chica_anime.jpg',
            'cost' => 50,
            'description' => 'Ilustración anime con tonos suaves.',
        ],
        'claro_bts' => [
            'key' => 'claro_bts',
            'name' => 'BTS Claro',
            'file' => 'claro_bts.jpg',
            'cost' => 50,
            'description' => 'Estilo estético claro y minimalista.',
        ],
        'dragon_ball' => [
            'key' => 'dragon_ball',
            'name' => 'Dragon Ball',
            'file' => 'dragon_ball.png',
            'cost' => 50,
            'description' => 'Fondo épico de anime de acción.',
        ],
        'anime_morado' => [
            'key' => 'anime_morado',
            'name' => 'Anime Neón Morado',
            'file' => 'anime_morado.jpg',
            'cost' => 50,
            'description' => 'Paisaje nocturno con tonalidades púrpuras.',
        ],
        'lofi_naturaleza' => [
            'key' => 'lofi_naturaleza',
            'name' => 'Lo-Fi Naturaleza',
            'file' => 'lofi_naturaleza.jpg',
            'cost' => 50,
            'description' => 'Ambiente relajante de estudio en el bosque.',
        ],
        'gris_pinguino' => [
            'key' => 'gris_pinguino',
            'name' => 'Pingüino Gris',
            'file' => 'gris_pinguino.jpeg',
            'cost' => 50,
            'description' => 'Fondo minimalista en tonos grises.',
        ],
        'verde_cactus' => [
            'key' => 'verde_cactus',
            'name' => 'Cactus Verde',
            'file' => 'verde_cactus.jpg',
            'cost' => 50,
            'description' => 'Naturaleza botánica en verde fresco.',
        ],
        'lofi_gato' => [
            'key' => 'lofi_gato',
            'name' => 'Lo-Fi Gato',
            'file' => 'lofi_gato.jpg',
            'cost' => 50,
            'description' => 'Ilustración lo-fi acogedora con un felino.',
        ],
    ],
];
