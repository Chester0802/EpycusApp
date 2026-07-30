<?php

declare(strict_types=1);

namespace App\Modules\StudyGroups\Domain\Exceptions;

use App\Shared\Domain\Exceptions\ConflictException;

final class AlreadyInSessionException extends ConflictException
{
    public function __construct(int $userId)
    {
        parent::__construct(
            message: 'Ya estás en una sesión de estudio activa. Sal de ella antes de unirte a otra.',
            code: 'STUDY_GROUPS.ALREADY_IN_SESSION',
            context: ['user_id' => $userId],
        );
    }
}
