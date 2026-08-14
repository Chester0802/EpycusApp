<?php

declare(strict_types=1);

namespace App\Modules\StudyGroups\Application\UseCases;

use App\Modules\Pomodoro\Domain\ValueObjects\SessionState as PomodoroState;
use App\Modules\Pomodoro\Infrastructure\Models\PomodoroSessionModel;
use App\Modules\StudyGroups\Application\Mappers\StudyGroupMapper;
use App\Modules\StudyGroups\Domain\Contracts\StudySessionRepositoryInterface;
use App\Modules\StudyGroups\Infrastructure\Models\StudySessionModel;
use Carbon\CarbonImmutable;

final readonly class PollSessionUseCase
{
    public function __construct(
        private StudySessionRepositoryInterface $repository,
        private StudyGroupMapper $mapper,
        private AdvancePhaseUseCase $advancePhase,
    ) {}

    /** @return array<string, mixed> */
    public function execute(int $sessionId, int $lastMessageId): array
    {
        $this->advancePhase->execute($sessionId);

        $session = $this->repository->findById($sessionId);

        $messages = $this->repository->getMessagesSince($sessionId, $lastMessageId);
        $participants = $this->repository->getParticipants($sessionId);

        $result = [
            'messages' => $messages->map(fn ($m) => $this->mapper->toMessageArray($m))->values()->all(),
            'participants' => $participants->map(fn ($p) => [
                'id' => $p->id,
                'alias' => $p->alias,
                'avatar_style' => $p->avatar_style,
                'avatar_gender' => $p->avatar_gender,
                'avatar_options' => isset($p->avatar_options) ? (is_array($p->avatar_options) ? $p->avatar_options : json_decode((string) $p->avatar_options, true)) : null,
                'joined_at' => $p->joined_at,
            ])->values()->all(),
        ];

        if ($session) {
            $result['room'] = $this->serializeRoom($session);
        }

        $activePomodoro = PomodoroSessionModel::query()
            ->where('study_group_session_id', $sessionId)
            ->whereIn('status', [PomodoroState::RUNNING, PomodoroState::PAUSED])
            ->latest('id')
            ->first();

        if ($activePomodoro) {
            $result['active_pomodoro'] = [
                'pomodoro_session_id' => $activePomodoro->id,
                'user_id' => $activePomodoro->user_id,
                'planned_minutes' => $activePomodoro->planned_minutes,
                'started_at' => $activePomodoro->started_at->toIso8601String(),
                'status' => $activePomodoro->status,
            ];
        }

        return $result;
    }

    /** @return array<string, mixed> */
    private function serializeRoom(StudySessionModel $session): array
    {
        return [
            'phase' => $session->phase,
            'phase_ends_at' => $session->phase_ends_at?->toIso8601String(),
            'current_cycle' => $session->current_cycle,
            'total_cycles' => $session->cycles,
            'focus_minutes' => $session->focus_minutes,
            'break_minutes' => $session->break_minutes,
            'state' => $session->state,
            'server_now' => CarbonImmutable::now()->toIso8601String(),
        ];
    }
}
