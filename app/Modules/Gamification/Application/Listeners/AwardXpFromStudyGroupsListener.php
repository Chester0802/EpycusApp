<?php

declare(strict_types=1);

namespace App\Modules\Gamification\Application\Listeners;

use App\Modules\Gamification\Application\UseCases\AwardXpUseCase;
use App\Modules\StudyGroups\Domain\Events\GroupMessageSent;
use App\Modules\StudyGroups\Domain\Events\ParticipantJoined;
use App\Modules\StudyGroups\Domain\Events\StudySessionCreated;

final class AwardXpFromStudyGroupsListener
{
    public function __construct(private AwardXpUseCase $awardXp) {}

    public function handle(ParticipantJoined | GroupMessageSent | StudySessionCreated $event): void
    {
        $this->awardXp->execute(
            userId: $event->userId,
            sourceType: 'study_group',
            sourceId: match (true) {
                $event instanceof GroupMessageSent => $event->messageId,
                default => $event->sessionId,
            },
            baseXp: match (true) {
                $event instanceof GroupMessageSent => (int) config('gamification.xp.study_group_message'),
                default => (int) config('gamification.xp.study_group_joined'),
            },
            dailyCap: (int) config('gamification.daily_caps.study_groups'),
            countsTowardStreak: true,
        );
    }
}
