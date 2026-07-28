<?php

declare(strict_types=1);

namespace App\Shared\Domain\Exceptions;

abstract class ConflictException extends DomainException
{
    public function __construct(
        string $message = 'Conflicto con el estado actual del recurso.',
        string $code = 'CONFLICT',
        array $context = [],
    ) {
        parent::__construct($message, $code, $context);
    }
}
