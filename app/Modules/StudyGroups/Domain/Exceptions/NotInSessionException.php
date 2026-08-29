<?php

declare(strict_types=1);

namespace App\Modules\StudyGroups\Domain\Exceptions;

use App\Shared\Domain\Exceptions\ForbiddenException;

final class NotInSessionException extends ForbiddenException
{
    public function __construct(int $sessionId)
    {
        parent::__construct(
            message: 'No perteneces a esta sesión de estudio.',
            code: 'STUDY_GROUPS.NOT_IN_SESSION',
            context: ['session_id' => $sessionId],
        );
    }
}
