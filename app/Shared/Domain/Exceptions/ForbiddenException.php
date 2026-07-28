<?php

declare(strict_types=1);

namespace App\Shared\Domain\Exceptions;

abstract class ForbiddenException extends DomainException
{
    public function __construct(
        string $message = 'No tienes permiso para realizar esta acción.',
        string $code = 'FORBIDDEN',
        array $context = [],
    ) {
        parent::__construct($message, $code, $context);
    }
}
