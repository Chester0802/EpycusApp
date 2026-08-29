<?php

declare(strict_types=1);

namespace App\Shared\Domain\Exceptions;

abstract class ValidationException extends DomainException
{
    /**
     * @param  array<string, mixed>  $context
     */
    public function __construct(
        string $message = 'Error de validación.',
        string $code = 'VALIDATION_ERROR',
        array $context = [],
    ) {
        parent::__construct($message, $code, $context);
    }
}
