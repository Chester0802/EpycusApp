<?php

declare(strict_types=1);

namespace App\Modules\StudyGroups\Domain\Exceptions;

use App\Shared\Domain\Exceptions\ForbiddenException;

final class RoomNotConfigurableException extends ForbiddenException
{
    public function __construct(string $reason)
    {
        parent::__construct(
            message: $reason,
            code: 'STUDY_GROUPS.ROOM_NOT_CONFIGURABLE',
        );
    }
}
