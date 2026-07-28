<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application\Mappers;

use App\Modules\Identity\Domain\Entities\Participant;
use App\Modules\Identity\Domain\ValueObjects\ParticipantCode;
use App\Modules\Identity\Domain\ValueObjects\UserId;
use App\Modules\Identity\Infrastructure\Models\ParticipantModel;

final class ParticipantMapper
{
    public function toDomain(ParticipantModel $model): Participant
    {
        return new Participant(
            userId: new UserId($model->user_id),
            participantCode: new ParticipantCode($model->participant_code),
        );
    }

    public function toPersistence(Participant $participant): array
    {
        return [
            'user_id' => $participant->userId()->value(),
            'participant_code' => $participant->participantCode()->value(),
            'consent_granted_at' => $participant->hasConsented() ? now() : null,
            'withdrawn_at' => $participant->isActive() ? null : now(),
        ];
    }
}
