<?php

declare(strict_types=1);

namespace App\Modules\Pomodoro\Domain\Exceptions;

use App\Shared\Domain\Exceptions\NotFoundException;

final class SessionNotFoundException extends NotFoundException
{
    public function __construct()
    {
        parent::__construct('La sesión de Pomodoro no existe o no te pertenece.');
    }
}
