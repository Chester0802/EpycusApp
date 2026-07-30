<?php

declare(strict_types=1);

namespace App\Modules\StudyGroups\Domain\Exceptions;

use App\Shared\Domain\Exceptions\ConflictException;

final class SessionFullException extends ConflictException
{
    public function __construct(int $sessionId)
    {
        parent::__construct(
            message: 'La sesión de estudio ya tiene el máximo de participantes.',
            code: 'STUDY_GROUPS.SESSION_FULL',
            context: ['session_id' => $sessionId],
        );
    }
}
