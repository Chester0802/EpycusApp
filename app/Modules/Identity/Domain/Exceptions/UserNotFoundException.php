<?php

declare(strict_types=1);

namespace App\Modules\Identity\Domain\Exceptions;

use App\Shared\Domain\Exceptions\NotFoundException;

final class UserNotFoundException extends NotFoundException
{
    public function __construct(int $userId)
    {
        parent::__construct(
            message: 'Usuario no encontrado.',
            code: 'IDENTITY.USER_NOT_FOUND',
            context: ['user_id' => $userId],
        );
    }
}
