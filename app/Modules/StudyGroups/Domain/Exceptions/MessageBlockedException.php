<?php

declare(strict_types=1);

namespace App\Modules\StudyGroups\Domain\Exceptions;

use App\Shared\Domain\Exceptions\ValidationException;

final class MessageBlockedException extends ValidationException
{
    public function __construct()
    {
        parent::__construct(
            message: 'El mensaje contiene palabras no permitidas.',
            code: 'STUDY_GROUPS.MESSAGE_BLOCKED',
        );
    }
}
