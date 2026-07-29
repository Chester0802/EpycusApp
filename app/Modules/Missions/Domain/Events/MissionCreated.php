<?php

declare(strict_types=1);

namespace App\Modules\Missions\Domain\Events;

final readonly class MissionCreated
{
    public function __construct(
        public int $missionId,
        public int $userId,
        public \DateTimeImmutable $occurredAt,
    ) {}
}
