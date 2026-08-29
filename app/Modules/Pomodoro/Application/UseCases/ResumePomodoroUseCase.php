<?php

declare(strict_types=1);

namespace App\Modules\Pomodoro\Application\UseCases;

use App\Modules\Pomodoro\Domain\Contracts\PomodoroRepositoryInterface;
use App\Modules\Pomodoro\Domain\Exceptions\InvalidSessionTransitionException;
use App\Modules\Pomodoro\Domain\Exceptions\SessionNotFoundException;
use App\Modules\Pomodoro\Domain\ValueObjects\SessionState;
use App\Modules\Pomodoro\Infrastructure\Models\PomodoroSessionModel;
use Carbon\Carbon;

/**
 * Sin evento propio a propósito: docs/01-MODULOS.md §3 solo lista
 * `PomodoroStarted/Completed/Abandoned/Paused` como eventos emitidos —
 * "reanudado" no es uno de ellos.
 */
final class ResumePomodoroUseCase
{
    public function __construct(private PomodoroRepositoryInterface $repository) {}

    public function execute(int $sessionId, int $userId): PomodoroSessionModel
    {
        $session = $this->repository->findByIdAndUser($sessionId, $userId);

        if ($session === null) {
            throw new SessionNotFoundException;
        }

        if ($session->status !== SessionState::PAUSED) {
            throw new InvalidSessionTransitionException('reanudar', $session->status);
        }

        $now = Carbon::now();
        // Orden importa: en Carbon 3, `$a->diffInSeconds($b)` da negativo
        // si $b es anterior a $a. `paused_at` es anterior a `$now`, así
        // que el receptor tiene que ser `paused_at` (ver el mismo
        // comentario, más detallado, en PomodoroSessionModel::elapsedActiveSeconds()).
        $pausedSeconds = (int) $session->paused_at->diffInSeconds($now);

        return $this->repository->update($session, [
            'status' => SessionState::RUNNING,
            'paused_at' => null,
            'total_paused_seconds' => $session->total_paused_seconds + $pausedSeconds,
        ]);
    }
}
