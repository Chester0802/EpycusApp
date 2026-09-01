<?php

declare(strict_types=1);

namespace App\Modules\Gamification\Application\Listeners;

use App\Modules\Gamification\Domain\Events\XpAwarded;
use App\Modules\Gamification\Infrastructure\Models\SkillModel;
use App\Modules\Gamification\Infrastructure\Models\UserSkillModel;
use App\Modules\Missions\Infrastructure\Models\MissionModel;
use App\Modules\DayPlanner\Infrastructure\Models\DailyRoutineModel;

final class AwardSkillXpListener
{
    public function handle(XpAwarded $event): void
    {
        if ($event->amount <= 0) {
            return;
        }

        $skillKey = $this->determineSkill($event->sourceType, $event->sourceId ?? null);
        
        if (! $skillKey) {
            return;
        }

        $skill = SkillModel::where('key', $skillKey)->first();
        if (! $skill) {
            return;
        }

        $userSkill = UserSkillModel::firstOrCreate(
            ['user_id' => $event->userId, 'skill_id' => $skill->id],
            ['xp' => 0, 'level' => 1]
        );

        $newXp = $userSkill->xp + $event->amount;
        $newLevel = (int) floor(sqrt($newXp / 100)) + 1;

        $userSkill->update([
            'xp' => $newXp,
            'level' => $newLevel,
        ]);
    }

    private function determineSkill(string $sourceType, ?int $sourceId = null): ?string
    {
        return match ($sourceType) {
            'pomodoro' => 'discipline',
            'habit' => 'discipline', // Or vitality depending on category, but default discipline
            'study_group' => 'intellect',
            'mission' => $this->getMissionSkill($sourceId),
            'routine' => 'vitality',
            default => 'creativity',
        };
    }

    private function getMissionSkill(?int $missionId): string
    {
        if (! $missionId) {
            return 'creativity';
        }

        $mission = MissionModel::find($missionId);
        if (! $mission) {
            return 'creativity';
        }

        // If belongs to a course -> Intellect
        if ($mission->course_id) {
            return 'intellect';
        }

        // If it is related to personal routines or health -> Vitality (needs proper category check, fallback to creativity)
        return 'creativity';
    }
}
