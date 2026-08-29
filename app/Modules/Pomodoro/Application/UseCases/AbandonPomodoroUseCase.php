<?php

declare(strict_types=1);

namespace App\Modules\Pomodoro\Application\UseCases;

use App\Modules\Pomodoro\Domain\Contracts\PomodoroRepositoryInterface;
use App\Modules\Pomodoro\Domain\Events\PomodoroAbandoned;
use App\Modules\Pomodoro\Domain\Exceptions\InvalidSessionTransitionException;
use App\Modules\Pomodoro\Domain\Exceptions\SessionNotFoundException;
use App\Modules\Pomodoro\Domain\ValueObjects\SessionState;
use App\Modules\Pomodoro\Infrastructure\Models\PomodoroSessionModel;
use Carbon\Carbon;
use Illuminate\Contracts\Events\Dispatcher;

/**
 * Nunca otorga XP (docs/03-GAMIFICACION.md §10: nada de penalizaciones,
 * pero tampoco premio por abandonar) — no hay listener suscrito a
 * `PomodoroAbandoned` que toque Gamification, a propósito.
 */
final class AbandonPomodoroUseCase
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

        if (! (new SessionState($session->status))->isActive()) {
            throw new InvalidSessionTransitionException('abandonar', $session->status);
        }

        $focusMinutes = intdiv($session->elapsedActiveSeconds(Carbon::now()), 60);

        $updated = $this->repository->update($session, [
            'status' => SessionState::ABANDONED,
            'ended_at' => Carbon::now(),
            'focus_minutes' => $focusMinutes,
        ]);

        $this->events->dispatch(new PomodoroAbandoned($session->id, $userId, $focusMinutes));

        return $updated;
    }
}
