<?php

declare(strict_types=1);

namespace App\Modules\Pomodoro\Domain\Exceptions;

use App\Shared\Domain\Exceptions\ConflictException;

final class ActiveSessionAlreadyExistsException extends ConflictException
{
    public function __construct()
    {
        parent::__construct('Ya tienes una sesión de Pomodoro activa. Termínala o abandónala antes de iniciar otra.');
    }
}
