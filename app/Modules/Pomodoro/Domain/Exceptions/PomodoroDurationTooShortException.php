<?php

declare(strict_types=1);

namespace App\Modules\Pomodoro\Domain\Exceptions;

use App\Shared\Domain\Exceptions\ValidationException;

/**
 * Anti-manipulación (docs/01-MODULOS.md §3): una sesión solo cuenta como
 * completada si el tiempo activo real llegó al 95% de lo planificado.
 */
final class PomodoroDurationTooShortException extends ValidationException
{
    public function __construct()
    {
        parent::__construct('Todavía no pasó suficiente tiempo para completar esta sesión.');
    }
}
