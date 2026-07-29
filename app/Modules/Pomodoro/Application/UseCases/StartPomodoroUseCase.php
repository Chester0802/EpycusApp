<?php

declare(strict_types=1);

namespace App\Modules\Pomodoro\Application\UseCases;

use App\Modules\Pomodoro\Domain\Contracts\PomodoroRepositoryInterface;
use App\Modules\Pomodoro\Domain\Events\PomodoroStarted;
use App\Modules\Pomodoro\Domain\Exceptions\ActiveSessionAlreadyExistsException;
use App\Modules\Pomodoro\Domain\ValueObjects\SessionState;
use App\Modules\Pomodoro\Infrastructure\Models\PomodoroSessionModel;
use Carbon\Carbon;
use Illuminate\Contracts\Events\Dispatcher;

final class StartPomodoroUseCase
{
    public function __construct(
        private PomodoroRepositoryInterface $repository,
        private ResolveStaleSessionUseCase $resolveStale,
        private Dispatcher $events,
    ) {}

    public function execute(int $userId, int $plannedMinutes, ?int $missionId = null): PomodoroSessionModel
    {
        // Antes de bloquear por "ya hay una activa", se resuelve primero
        // si esa sesión "activa" en realidad ya venció (ver
        // ResolveStaleSessionUseCase) — así, si el usuario cerró el
        // navegador con un Pomodoro corriendo y vuelve horas después a
        // iniciar uno nuevo directamente, el anterior se cierra solo en
        // vez de bloquearlo con un error que no entendería.
        $resolved = $this->resolveStale->execute($userId);

        if ($resolved->session !== null) {
            throw new ActiveSessionAlreadyExistsException;
        }

        $session = $this->repository->create([
            'user_id' => $userId,
            'mission_id' => $missionId,
            'planned_minutes' => $plannedMinutes,
            'started_at' => Carbon::now(),
            'status' => SessionState::RUNNING,
        ]);

        $this->events->dispatch(new PomodoroStarted($session->id, $userId, $plannedMinutes));

        return $session;
    }
}
