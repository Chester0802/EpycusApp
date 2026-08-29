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
            studentCode: $model->student_code,
            whatsapp: $model->whatsapp,
            consentGrantedAt: $model->consent_granted_at?->toDateTimeImmutable(),
            enrolledAt: $model->enrolled_at?->toDateTimeImmutable(),
            withdrawnAt: $model->withdrawn_at?->toDateTimeImmutable(),
        );
    }

    /** @return array<string, mixed> */
    public function toPersistence(Participant $participant): array
    {
        return [
            'user_id' => $participant->userId()->value(),
            'participant_code' => $participant->participantCode()->value(),
            'student_code' => $participant->studentCode(),
            'whatsapp' => $participant->whatsapp(),
            'consent_granted_at' => $participant->consentGrantedAt(),
            'enrolled_at' => $participant->enrolledAt(),
            'withdrawn_at' => $participant->withdrawnAt(),
        ];
    }
}
