<?php

declare(strict_types=1);

namespace App\Modules\Identity\Domain\Exceptions;

use App\Shared\Domain\Exceptions\ConflictException;

final class ConsentAlreadyGrantedException extends ConflictException
{
    public function __construct(int $userId)
    {
        parent::__construct(
            message: 'El consentimiento ya fue otorgado anteriormente.',
            code: 'IDENTITY.CONSENT_ALREADY_GRANTED',
            context: ['user_id' => $userId],
        );
    }
}
