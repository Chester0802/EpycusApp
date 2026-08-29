<?php

declare(strict_types=1);

namespace App\Modules\Pomodoro\Application\UseCases;

use App\Modules\Pomodoro\Domain\Contracts\PomodoroRepositoryInterface;
use App\Modules\Pomodoro\Domain\Events\PomodoroCompleted;
use App\Modules\Pomodoro\Domain\Exceptions\InvalidSessionTransitionException;
use App\Modules\Pomodoro\Domain\Exceptions\PomodoroDurationTooShortException;
use App\Modules\Pomodoro\Domain\Exceptions\SessionNotFoundException;
use App\Modules\Pomodoro\Domain\ValueObjects\SessionState;
use App\Modules\Pomodoro\Infrastructure\Models\PomodoroSessionModel;
use Carbon\Carbon;
use Illuminate\Contracts\Events\Dispatcher;

/**
 * Anti-manipulación real (docs/01-MODULOS.md §3): a diferencia del ejemplo
 * del documento (que valida un `completed_at` enviado por el cliente), acá
 * ni `started_at` ni `completed_at` vienen del cliente en ningún momento —
 * los dos son el reloj del servidor (`started_at` al crear la sesión,
 * `Carbon::now()` acá). El cliente no tiene forma de mentir sobre cuánto
 * tiempo pasó porque nunca se le pregunta.
 */
final class CompletePomodoroUseCase
{
    public function __construct(
        private PomodoroRepositoryInterface $repository,
        private Dispatcher $events,
    ) {}

    public function execute(int $sessionId, int $userId, bool $autoResolved = false): PomodoroSessionModel
    {
        $session = $this->repository->findByIdAndUser($sessionId, $userId);

        if ($session === null) {
            throw new SessionNotFoundException;
        }

        if (! (new SessionState($session->status))->isActive()) {
            throw new InvalidSessionTransitionException('completar', $session->status);
        }

        $elapsedSeconds = $session->elapsedActiveSeconds(Carbon::now());
        $requiredSeconds = (int) round($session->planned_minutes * 60 * 0.95);

        if ($elapsedSeconds < $requiredSeconds) {
            throw new PomodoroDurationTooShortException;
        }

        // No se reporta más foco que lo planificado: quedarse conectado de
        // más (o que el auto-resuelto tarde en detectarlo) no debe inflar
        // "minutos de foco" más allá del bloque real que se planificó.
        $focusMinutes = min($session->planned_minutes, intdiv($elapsedSeconds, 60));

        $updated = $this->repository->update($session, [
            'status' => SessionState::COMPLETED,
            'ended_at' => Carbon::now(),
            'focus_minutes' => $focusMinutes,
        ]);

        $this->events->dispatch(new PomodoroCompleted($session->id, $userId, $focusMinutes, $autoResolved));

        return $updated;
    }
}
