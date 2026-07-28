<?php

declare(strict_types=1);

use App\Modules\Identity\Domain\ValueObjects\Career;

return [
    // La lista cerrada vive en Career (Domain) — este archivo solo la expone
    // a la Presentation/Infrastructure. No la dupliques aquí.
    'styles' => Career::groupedByStyle(),
    'cycles' => range(1, 10),
    'institution_types' => ['universidad', 'instituto'],
    'default_style' => 'technical',
];
