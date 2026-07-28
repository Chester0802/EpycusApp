<?php

declare(strict_types=1);

namespace App\Modules\Identity\Domain\Exceptions;

use App\Shared\Domain\Exceptions\ConflictException;

final class EmailAlreadyTakenException extends ConflictException
{
    public function __construct(string $email)
    {
        parent::__construct(
            message: 'Este correo ya está registrado.',
            code: 'IDENTITY.EMAIL_TAKEN',
            context: ['email' => $email],
        );
    }
}
