<?php

declare(strict_types=1);

namespace App\Modules\Pomodoro\Domain\Exceptions;

use App\Shared\Domain\Exceptions\ConflictException;

final class InvalidSessionTransitionException extends ConflictException
{
    public function __construct(string $attemptedAction, string $currentStatus)
    {
        parent::__construct("No se puede {$attemptedAction} una sesión en estado \"{$currentStatus}\".");
    }
}
