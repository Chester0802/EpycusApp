<?php

declare(strict_types=1);

namespace App\Modules\StudyGroups\Domain\Exceptions;

use App\Shared\Domain\Exceptions\ForbiddenException;

final class ChatBlockedException extends ForbiddenException
{
    public function __construct()
    {
        parent::__construct(
            message: 'El chat está desactivado durante la fase de enfoque.',
            code: 'STUDY_GROUPS.CHAT_BLOCKED',
        );
    }
}
