<?php

declare(strict_types=1);

namespace App\Modules\StudyGroups\Domain\Exceptions;

use App\Shared\Domain\Exceptions\NotFoundException;

final class SessionNotFoundException extends NotFoundException
{
    public function __construct(int $sessionId)
    {
        parent::__construct(
            message: 'Sesión de estudio no encontrada.',
            code: 'STUDY_GROUPS.SESSION_NOT_FOUND',
            context: ['session_id' => $sessionId],
        );
    }
}
