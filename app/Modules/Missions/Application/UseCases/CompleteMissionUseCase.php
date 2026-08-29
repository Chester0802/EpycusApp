<?php

declare(strict_types=1);

namespace App\Modules\Missions\Application\UseCases;

use App\Modules\Missions\Domain\Contracts\MissionRepositoryInterface;
use App\Modules\Missions\Domain\Events\MissionCompleted;
use Carbon\Carbon;
use Illuminate\Contracts\Events\Dispatcher;

final class CompleteMissionUseCase
{
    public function __construct(
        private MissionRepositoryInterface $repository,
        private Dispatcher $events,
    ) {}

    public function execute(int $missionId, int $userId): void
    {
        $mission = $this->repository->findByIdAndUser($missionId, $userId);

        if (! $mission || $mission->completed_at) {
            return;
        }

        $now = Carbon::now();
        $daysEarlyOrLate = null;

        if ($mission->due_date) {
            $due = Carbon::parse($mission->due_date);
            $daysEarlyOrLate = (int) $due->diffInDays($now, false) * -1;
        }

        $xp = match ($mission->difficulty) {
            'easy' => (int) config('gamification.xp.mission_easy', 20),
            'medium' => (int) config('gamification.xp.mission_medium', 30),
            'hard' => (int) config('gamification.xp.mission_hard', 40),
            default => 20,
        };

        $completedToday = $this->repository->countCompletedToday($userId);
        if ($completedToday >= 3) {
            $xp = 0;
        }

        $this->repository->update($mission, [
            'completed_at' => $now,
            'days_early_or_late' => $daysEarlyOrLate,
            'xp_awarded' => $xp,
            'is_overdue' => false,
        ]);

        $mission->subtasks()->where('is_completed', false)->update([
            'is_completed' => true,
            'completed_at' => $now,
        ]);

        session()->flash('xp_awarded', $xp);

        $this->events->dispatch(new MissionCompleted(
            missionId: $mission->id,
            userId: $userId,
            xpAwarded: $xp,
            daysEarlyOrLate: $daysEarlyOrLate,
            occurredAt: new \DateTimeImmutable,
        ));
    }
}
