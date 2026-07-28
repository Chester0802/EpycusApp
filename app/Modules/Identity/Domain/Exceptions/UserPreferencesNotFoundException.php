<?php

declare(strict_types=1);

namespace App\Modules\Identity\Domain\Exceptions;

use App\Shared\Domain\Exceptions\NotFoundException;

final class UserPreferencesNotFoundException extends NotFoundException
{
    public function __construct(int $userId)
    {
        parent::__construct(
            message: 'Preferencias no encontradas.',
            code: 'IDENTITY.PREFERENCES_NOT_FOUND',
            context: ['user_id' => $userId],
        );
    }
}
