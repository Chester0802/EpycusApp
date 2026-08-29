<?php

declare(strict_types=1);

namespace App\Modules\StudyGroups\Domain\Exceptions;

use App\Shared\Domain\Exceptions\ValidationException;

final class InvalidRoomPhaseException extends ValidationException
{
    public function __construct(string $phase)
    {
        parent::__construct(
            message: "Fase de sala inválida: {$phase}.",
            code: 'STUDY_GROUPS.INVALID_ROOM_PHASE',
            context: ['phase' => $phase],
        );
    }
}
