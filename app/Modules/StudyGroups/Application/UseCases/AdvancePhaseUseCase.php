<?php

declare(strict_types=1);

namespace App\Modules\StudyGroups\Application\UseCases;

use App\Modules\StudyGroups\Domain\Contracts\StudySessionRepositoryInterface;
use App\Modules\StudyGroups\Domain\Events\BreakPhaseStarted;
use App\Modules\StudyGroups\Domain\Events\FocusPhaseStarted;
use App\Modules\StudyGroups\Domain\Events\RoomCompleted;
use App\Modules\StudyGroups\Domain\Events\RoomPhaseCompleted;
use App\Modules\StudyGroups\Domain\Exceptions\RoomNotConfigurableException;
use App\Modules\StudyGroups\Domain\Exceptions\SessionNotFoundException;
use App\Modules\StudyGroups\Domain\ValueObjects\RoomPhase;
use App\Modules\StudyGroups\Domain\ValueObjects\SessionState;
use App\Modules\StudyGroups\Infrastructure\Models\StudySessionModel;
use Carbon\Carbon;
use Illuminate\Contracts\Events\Dispatcher;

final readonly class AdvancePhaseUseCase
{
    public function __construct(
        private StudySessionRepositoryInterface $repository,
        private Dispatcher $events,
    ) {}

    /**
     * @return array{advanced: bool, phase: string, cycle: int}
     */
    public function execute(int $sessionId, ?int $userId = null): array
    {
        $session = $this->repository->findById($sessionId);

        if (! $session || $session->state === SessionState::CLOSED) {
            throw new SessionNotFoundException($sessionId);
        }

        if ($userId !== null && $session->host_id !== $userId) {
            throw new RoomNotConfigurableException('Solo el anfitrión puede avanzar la fase.');
        }

        $phase = RoomPhase::from($session->phase);
        $now = Carbon::now();

        if ($phase->isCompleted()) {
            return ['advanced' => false, 'phase' => $phase->value(), 'cycle' => $session->current_cycle];
        }

        if ($phase->isIdle()) {
            // Solo se avanza de idle → focus cuando el host lo solicita
            // explícitamente (userId no null). El poll llama sin userId
            // y nunca debe auto-iniciar una sala en idle.
            if ($userId === null) {
                return ['advanced' => false, 'phase' => $phase->value(), 'cycle' => $session->current_cycle];
            }
            return $this->startFocus($session, $now);
        }

        if ($phase->isRunning() && $session->phase_ends_at && $now->lessThan($session->phase_ends_at)) {
            return ['advanced' => false, 'phase' => $phase->value(), 'cycle' => $session->current_cycle];
        }

        if ($phase->isFocus()) {
            $this->events->dispatch(new RoomPhaseCompleted(
                sessionId: $session->id,
                phase: 'focus',
                cycle: $session->current_cycle,
                occurredAt: new \DateTimeImmutable,
            ));

            if ($session->current_cycle >= $session->cycles) {
                return $this->completeRoom($session, $now);
            }

            return $this->startBreak($session, $now);
        }

        if ($phase->isBreak()) {
            $this->events->dispatch(new RoomPhaseCompleted(
                sessionId: $session->id,
                phase: 'break',
                cycle: $session->current_cycle,
                occurredAt: new \DateTimeImmutable,
            ));

            $nextCycle = $session->current_cycle + 1;

            if ($nextCycle > $session->cycles) {
                return $this->completeRoom($session, $now);
            }

            return $this->startFocus($session, $now, $nextCycle);
        }

        return ['advanced' => false, 'phase' => $phase->value(), 'cycle' => $session->current_cycle];
    }

    /**
     * @return array{advanced: bool, phase: string, cycle: int}
     */
    private function startFocus(StudySessionModel $session, Carbon $now, ?int $overrideCycle = null): array
    {
        $cycle = $overrideCycle ?? $session->current_cycle + 1;
        $endsAt = Carbon::now()->addMinutes($session->focus_minutes);

        $this->repository->update($session, [
            'phase' => RoomPhase::FOCUS,
            'current_cycle' => $cycle,
            'phase_started_at' => $now,
            'phase_ends_at' => $endsAt,
            'state' => SessionState::RUNNING,
        ]);

        $this->events->dispatch(new FocusPhaseStarted(
            sessionId: $session->id,
            cycle: $cycle,
            startedAt: new \DateTimeImmutable($now->toIso8601String()),
            endsAt: new \DateTimeImmutable($endsAt->toIso8601String()),
        ));

        return ['advanced' => true, 'phase' => RoomPhase::FOCUS, 'cycle' => $cycle];
    }

    /**
     * @return array{advanced: bool, phase: string, cycle: int}
     */
    private function startBreak(StudySessionModel $session, Carbon $now): array
    {
        $endsAt = Carbon::now()->addMinutes($session->break_minutes);

        $this->repository->update($session, [
            'phase' => RoomPhase::BREAK,
            'phase_started_at' => $now,
            'phase_ends_at' => $endsAt,
        ]);

        $this->events->dispatch(new BreakPhaseStarted(
            sessionId: $session->id,
            cycle: $session->current_cycle,
            startedAt: new \DateTimeImmutable($now->toIso8601String()),
            endsAt: new \DateTimeImmutable($endsAt->toIso8601String()),
        ));

        return ['advanced' => true, 'phase' => RoomPhase::BREAK, 'cycle' => $session->current_cycle];
    }

    /**
     * @return array{advanced: bool, phase: string, cycle: int}
     */
    private function completeRoom(StudySessionModel $session, Carbon $now): array
    {
        $this->repository->update($session, [
            'phase' => RoomPhase::COMPLETED,
            'phase_ends_at' => $now,
            'state' => SessionState::COMPLETED,
        ]);

        $this->events->dispatch(new RoomCompleted(
            sessionId: $session->id,
            totalCycles: $session->cycles,
            occurredAt: new \DateTimeImmutable,
        ));

        return ['advanced' => true, 'phase' => RoomPhase::COMPLETED, 'cycle' => $session->cycles];
    }
}
