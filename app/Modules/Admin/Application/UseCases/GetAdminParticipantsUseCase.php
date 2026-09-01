<?php

declare(strict_types=1);

namespace App\Modules\Admin\Application\UseCases;

use Illuminate\Support\Facades\DB;

final class GetAdminParticipantsUseCase
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function execute(): array
    {
        $participants = DB::table('participants')
            ->join('users', 'users.id', '=', 'participants.user_id')
            ->leftJoin('user_progress', 'user_progress.user_id', '=', 'users.id')
            ->where('users.role', 'student')
            ->select([
                'users.id as user_id',
                'participants.participant_code',
                'users.alias',
                'users.career',
                'users.cycle',
                'users.institution_type',
                'user_progress.current_level',
                'user_progress.current_phase',
                'user_progress.total_xp',
                'user_progress.current_streak',
                'users.updated_at',
                'users.created_at',
            ])
            ->orderBy('user_progress.total_xp', 'desc')
            ->get();

        if ($participants->isEmpty()) {
            return [];
        }

        $userIds = $participants->pluck('user_id')->all();

        // 1. Obtener última actividad real por cada fuente de datos
        $sessionActivities = DB::table('sessions')
            ->whereIn('user_id', $userIds)
            ->select('user_id', DB::raw('MAX(last_activity) as max_val'))
            ->groupBy('user_id')
            ->pluck('max_val', 'user_id');

        $xpActivities = DB::table('xp_transactions')
            ->whereIn('user_id', $userIds)
            ->select('user_id', DB::raw('MAX(created_at) as max_val'))
            ->groupBy('user_id')
            ->pluck('max_val', 'user_id');

        $pomodoroActivities = DB::table('pomodoro_sessions')
            ->whereIn('user_id', $userIds)
            ->select('user_id', DB::raw('MAX(started_at) as max_val'))
            ->groupBy('user_id')
            ->pluck('max_val', 'user_id');

        $habitActivities = DB::table('habit_completions')
            ->join('habits', 'habits.id', '=', 'habit_completions.habit_id')
            ->whereIn('habits.user_id', $userIds)
            ->select('habits.user_id', DB::raw('MAX(habit_completions.created_at) as max_val'))
            ->groupBy('habits.user_id')
            ->pluck('max_val', 'habits.user_id');

        $journalActivities = DB::table('journal_entries')
            ->whereIn('user_id', $userIds)
            ->select('user_id', DB::raw('MAX(created_at) as max_val'))
            ->groupBy('user_id')
            ->pluck('max_val', 'user_id');

        $aiActivities = DB::table('ai_messages')
            ->join('ai_conversations', 'ai_conversations.id', '=', 'ai_messages.conversation_id')
            ->whereIn('ai_conversations.user_id', $userIds)
            ->select('ai_conversations.user_id', DB::raw('MAX(ai_messages.created_at) as max_val'))
            ->groupBy('ai_conversations.user_id')
            ->pluck('max_val', 'ai_conversations.user_id');

        return $participants->map(function ($p) use (
            $sessionActivities,
            $xpActivities,
            $pomodoroActivities,
            $habitActivities,
            $journalActivities,
            $aiActivities
        ) {
            $uId = (int) $p->user_id;

            // Recopilar timestamps candidatos
            $candidates = [];

            if (isset($sessionActivities[$uId])) {
                $candidates[] = \Carbon\Carbon::createFromTimestamp($sessionActivities[$uId]);
            }
            if (!empty($xpActivities[$uId])) {
                $candidates[] = \Carbon\Carbon::parse($xpActivities[$uId]);
            }
            if (!empty($pomodoroActivities[$uId])) {
                $candidates[] = \Carbon\Carbon::parse($pomodoroActivities[$uId]);
            }
            if (!empty($habitActivities[$uId])) {
                $candidates[] = \Carbon\Carbon::parse($habitActivities[$uId]);
            }
            if (!empty($journalActivities[$uId])) {
                $candidates[] = \Carbon\Carbon::parse($journalActivities[$uId]);
            }
            if (!empty($aiActivities[$uId])) {
                $candidates[] = \Carbon\Carbon::parse($aiActivities[$uId]);
            }
            if (!empty($p->updated_at)) {
                $candidates[] = \Carbon\Carbon::parse($p->updated_at);
            }
            if (!empty($p->created_at)) {
                $candidates[] = \Carbon\Carbon::parse($p->created_at);
            }

            $lastActiveCarbon = null;
            foreach ($candidates as $c) {
                if ($lastActiveCarbon === null || $c->greaterThan($lastActiveCarbon)) {
                    $lastActiveCarbon = $c;
                }
            }

            $lastActiveFormatted = 'Nunca';
            if ($lastActiveCarbon !== null) {
                $lastActiveCarbon->timezone('America/Lima');
                $lastActiveFormatted = $lastActiveCarbon->format('d/m/Y H:i');
            }

            return [
                'user_id'          => $p->user_id,
                'participant_code' => $p->participant_code ?? 'P-UNKNOWN',
                'alias'            => $p->alias ?? '—',
                'career'           => $p->career ?? '—',
                'cycle'            => $p->cycle ? "Ciclo {$p->cycle}" : '—',
                'institution_type' => $p->institution_type ?? '—',
                'current_level'    => $p->current_level ?? 1,
                'current_phase'    => $p->current_phase ?? 1,
                'total_xp'         => $p->total_xp ?? 0,
                'current_streak'   => $p->current_streak ?? 0,
                'last_active_at'   => $lastActiveFormatted,
            ];
        })->toArray();
    }
}
