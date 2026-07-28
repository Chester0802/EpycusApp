<?php

declare(strict_types=1);

namespace App\Shared\Domain\Exceptions;

abstract class DomainException extends \RuntimeException
{
    private string $errorCode;
    private array $context;

    public function __construct(
        string $message = '',
        string $code = 'DOMAIN_ERROR',
        array $context = [],
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
        $this->errorCode = $code;
        $this->context = $context;
    }

    public function context(): array
    {
        return $this->context;
    }

    public function errorCode(): string
    {
        return $this->errorCode;
    }
}
