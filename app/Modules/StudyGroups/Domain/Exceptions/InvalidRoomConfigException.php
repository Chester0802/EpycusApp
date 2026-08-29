<?php

declare(strict_types=1);

namespace App\Modules\StudyGroups\Domain\Exceptions;

use App\Shared\Domain\Exceptions\ValidationException;

final class InvalidRoomConfigException extends ValidationException
{
    public function __construct(string $reason)
    {
        parent::__construct(
            message: $reason,
            code: 'STUDY_GROUPS.INVALID_ROOM_CONFIG',
        );
    }
}
