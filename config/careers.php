<?php

declare(strict_types=1);

return [
    'styles' => [
        'health' => ['Medicina', 'Enfermería', 'Obstetricia'],
        'business' => ['Administración de Empresas', 'Contabilidad'],
        'technical' => ['Ingeniería Civil', 'Ingeniería Industrial', 'Ingeniería de Minas', 'Arquitectura'],
        'systems' => ['Ingeniería de Sistemas'],
        'law' => ['Derecho'],
    ],
    'cycles' => range(1, 10),
    'institution_types' => ['universidad', 'instituto'],
    'default_style' => 'technical',
];
