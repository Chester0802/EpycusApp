<?php

declare(strict_types=1);

namespace App\Shared\Domain\Exceptions;

abstract class NotFoundException extends DomainException
{
    public function __construct(
        string $message = 'Recurso no encontrado.',
        string $code = 'NOT_FOUND',
        array $context = [],
    ) {
        parent::__construct($message, $code, $context);
    }
}
