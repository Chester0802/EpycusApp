<?php

declare(strict_types=1);

namespace App\Modules\Pomodoro\Application\UseCases;

use App\Modules\Pomodoro\Application\DTOs\ActiveSessionResultDTO;
use App\Modules\Pomodoro\Domain\Contracts\PomodoroRepositoryInterface;
use App\Modules\Pomodoro\Domain\ValueObjects\SessionState;
use Carbon\Carbon;

/**
 * Resuelve el caso central que pidió el usuario: "le pongo en iniciar, y
 * luego salgo del navegador". docs/01-MODULOS.md §3 decidió a propósito
 * NO mantener un temporizador en el servidor (1 núcleo de CPU, 40 workers
 * — un timer server-side obligaría a pollear a todos los usuarios a la
 * vez). La consecuencia es que nadie "avisa" cuando el tiempo planificado
 * se cumple mientras el usuario no está mirando la pantalla — así que en
 * vez de eso, esto se resuelve de forma perezosa: cada vez que el usuario
 * vuelve a abrir el módulo (`GetActiveSessionUseCase`, llamado también
 * desde `StartPomodoroUseCase` antes de permitir una sesión nueva), se
 * comprueba si la sesión que quedó `running` ya debería haber terminado.
 *
 * Una sesión `paused` **nunca** se resuelve sola acá, sin importar cuánto
 * tiempo lleve pausada — pausar detiene el reloj a propósito, así que un
 * pausado de 3 días no es distinto de uno de 3 minutos; solo `running`
 * puede "vencer".
 */
final class ResolveStaleSessionUseCase
{
    public function __construct(
        private PomodoroRepositoryInterface $repository,
        private CompletePomodoroUseCase $completePomodoro,
    ) {}

    public function execute(int $userId): ActiveSessionResultDTO
    {
        $session = $this->repository->findActiveForUser($userId);

        if ($session === null) {
            return new ActiveSessionResultDTO(null);
        }

        if ($session->status !== SessionState::RUNNING) {
            return new ActiveSessionResultDTO($session);
        }

        $elapsedSeconds = $session->elapsedActiveSeconds(Carbon::now());

        if ($elapsedSeconds < $session->planned_minutes * 60) {
            return new ActiveSessionResultDTO($session);
        }

        $completed = $this->completePomodoro->execute($session->id, $userId, autoResolved: true);

        return new ActiveSessionResultDTO(null, $completed->focus_minutes);
    }
}
