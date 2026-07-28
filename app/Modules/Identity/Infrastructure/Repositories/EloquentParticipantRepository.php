<?php

declare(strict_types=1);

namespace App\Modules\Identity\Infrastructure\Repositories;

use App\Modules\Identity\Application\Mappers\ParticipantMapper;
use App\Modules\Identity\Domain\Contracts\ParticipantRepositoryInterface;
use App\Modules\Identity\Domain\Entities\Participant;
use App\Modules\Identity\Domain\ValueObjects\ParticipantCode;
use App\Modules\Identity\Domain\ValueObjects\UserId;
use App\Modules\Identity\Infrastructure\Models\ParticipantModel;

final readonly class EloquentParticipantRepository implements ParticipantRepositoryInterface
{
    public function __construct(private ParticipantMapper $mapper) {}

    public function findByUserId(UserId $userId): ?Participant
    {
        $model = ParticipantModel::where('user_id', $userId->value())->first();

        return $model ? $this->mapper->toDomain($model) : null;
    }

    public function findByCode(ParticipantCode $code): ?Participant
    {
        $model = ParticipantModel::where('participant_code', $code->value())->first();

        return $model ? $this->mapper->toDomain($model) : null;
    }

    public function save(Participant $participant): void
    {
        $data = $this->mapper->toPersistence($participant);
        unset($data['user_id']);

        ParticipantModel::updateOrCreate(
            ['user_id' => $participant->userId()->value()],
            $data,
        );
    }
}
