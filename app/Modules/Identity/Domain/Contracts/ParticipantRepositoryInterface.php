<?php

declare(strict_types=1);

namespace App\Modules\Identity\Domain\Contracts;

use App\Modules\Identity\Domain\Entities\Participant;
use App\Modules\Identity\Domain\ValueObjects\ParticipantCode;
use App\Modules\Identity\Domain\ValueObjects\UserId;

interface ParticipantRepositoryInterface
{
    public function findByUserId(UserId $userId): ?Participant;

    public function findByCode(ParticipantCode $code): ?Participant;

    public function save(Participant $participant): void;
}
