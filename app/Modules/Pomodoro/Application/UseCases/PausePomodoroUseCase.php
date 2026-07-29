<?php

declare(strict_types=1);

namespace App\Modules\Pomodoro\Application\UseCases;

use App\Modules\Pomodoro\Domain\Contracts\PomodoroRepositoryInterface;
use App\Modules\Pomodoro\Domain\Events\PomodoroPaused;
use App\Modules\Pomodoro\Domain\Exceptions\InvalidSessionTransitionException;
use App\Modules\Pomodoro\Domain\Exceptions\SessionNotFoundException;
use App\Modules\Pomodoro\Domain\ValueObjects\SessionState;
use App\Modules\Pomodoro\Infrastructure\Models\PomodoroSessionModel;
use Carbon\Carbon;
use Illuminate\Contracts\Events\Dispatcher;

final class PausePomodoroUseCase
{
    public function __construct(
        private PomodoroRepositoryInterface $repository,
        private Dispatcher $events,
    ) {}

    public function execute(int $sessionId, int $userId): PomodoroSessionModel
    {
        $session = $this->repository->findByIdAndUser($sessionId, $userId);

        if ($session === null) {
            throw new SessionNotFoundException;
        }

        if ($session->status !== SessionState::RUNNING) {
            throw new InvalidSessionTransitionException('pausar', $session->status);
        }

        $updated = $this->repository->update($session, [
            'status' => SessionState::PAUSED,
            'paused_at' => Carbon::now(),
        ]);

        $this->events->dispatch(new PomodoroPaused($session->id, $userId));

        return $updated;
    }
}
