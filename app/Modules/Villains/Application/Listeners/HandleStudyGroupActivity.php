<?php

declare(strict_types=1);

namespace App\Modules\Villains\Application\Listeners;

use App\Modules\StudyGroups\Domain\Events\GroupMessageSent;
use App\Modules\StudyGroups\Domain\Events\ParticipantJoined;
use App\Modules\StudyGroups\Domain\Events\StudySessionCreated;
use App\Modules\Villains\Application\DTOs\ApplyDamageDTO;
use App\Modules\Villains\Application\UseCases\ApplyDamageUseCase;

final class HandleStudyGroupActivity
{
    public function __construct(
        private ApplyDamageUseCase $applyDamage,
    ) {}

    public function handle(ParticipantJoined|GroupMessageSent|StudySessionCreated $event): void
    {
        $this->applyDamage->execute(new ApplyDamageDTO(
            userId: $event->userId,
            sourceType: 'study_group',
            occurredAt: match (true) {
                $event instanceof ParticipantJoined => $event->occurredAt,
                $event instanceof GroupMessageSent => $event->occurredAt,
                $event instanceof StudySessionCreated => $event->occurredAt,
            },
        ));
    }
}
