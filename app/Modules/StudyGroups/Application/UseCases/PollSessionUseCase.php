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
            'participants' => $participants->map(function ($p) {
                // El query del repositorio incluye el pivot o usa DB::table
                $pivot = \Illuminate\Support\Facades\DB::table('session_participants')
                    ->where('session_id', $p->pivot->session_id ?? 1)
                    ->where('user_id', $p->id)
                    ->first();

                return [
                    'id' => $p->id,
                    'alias' => $p->alias,
                    'avatar_style' => $p->avatar_style,
                    'avatar_gender' => $p->avatar_gender,
                    'avatar_options' => isset($p->avatar_options) ? (is_array($p->avatar_options) ? $p->avatar_options : json_decode((string) $p->avatar_options, true)) : null,
                    'joined_at' => $p->joined_at,
                    'pos_x' => $pivot ? $pivot->pos_x : null,
                    'pos_y' => $pivot ? $pivot->pos_y : null,
                ];
            })->values()->all(),
        ];

        if ($session) {
            $result['room'] = $this->serializeRoom($session);
        }

        // Obtener todos los pomodoros activos de los participantes de esta sala
        $activePomodoros = PomodoroSessionModel::query()
            ->where('study_group_session_id', $sessionId)
            ->whereIn('status', [PomodoroState::RUNNING, PomodoroState::PAUSED])
            ->get();

        $result['active_pomodoros'] = $activePomodoros->map(fn ($pom) => [
            'pomodoro_session_id' => $pom->id,
            'user_id' => $pom->user_id,
            'planned_minutes' => $pom->planned_minutes,
            'started_at' => $pom->started_at->toIso8601String(),
            'status' => $pom->status,
        ])->all();

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
