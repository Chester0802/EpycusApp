<?php

declare(strict_types=1);

namespace App\Modules\Pomodoro\Infrastructure\Repositories;

use App\Modules\Pomodoro\Domain\Contracts\PomodoroRepositoryInterface;
use App\Modules\Pomodoro\Domain\ValueObjects\SessionState;
use App\Modules\Pomodoro\Infrastructure\Models\PomodoroSessionModel;
use Carbon\Carbon;
use Illuminate\Support\Collection;

final class EloquentPomodoroRepository implements PomodoroRepositoryInterface
{
    public function create(array $data): PomodoroSessionModel
    {
        return PomodoroSessionModel::create($data);
    }

    public function findActiveForUser(int $userId): ?PomodoroSessionModel
    {
        return PomodoroSessionModel::query()
            ->where('user_id', $userId)
            ->whereIn('status', [SessionState::RUNNING, SessionState::PAUSED])
            ->latest('id')
            ->first();
    }

    public function findByIdAndUser(int $id, int $userId): ?PomodoroSessionModel
    {
        return PomodoroSessionModel::query()
            ->where('id', $id)
            ->where('user_id', $userId)
            ->first();
    }

    public function update(PomodoroSessionModel $session, array $data): PomodoroSessionModel
    {
        $session->update($data);

        return $session->fresh();
    }

    public function todaysSessionsForUser(int $userId): Collection
    {
        return PomodoroSessionModel::query()
            ->where('user_id', $userId)
            ->whereDate('started_at', Carbon::now('America/Lima')->toDateString())
            ->orderByDesc('started_at')
            ->get();
    }

    public function sessionsSinceForUser(int $userId, \DateTimeImmutable $since): Collection
    {
        return PomodoroSessionModel::query()
            ->where('user_id', $userId)
            ->where('started_at', '>=', $since->format('Y-m-d H:i:s'))
            ->orderByDesc('started_at')
            ->get();
    }
}
