<?php

declare(strict_types=1);

namespace App\Modules\Admin\Application\UseCases;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

final class GetAdminDropoutUseCase
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
                'user_progress.current_level',
                'user_progress.current_streak',
                'users.updated_at',
                'users.created_at',
            ])
            ->get();

        if ($participants->isEmpty()) {
            return [];
        }

        $userIds = $participants->pluck('user_id')->all();

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

        $nowLima = Carbon::now('America/Lima');
        $threeDaysAgo = Carbon::now('America/Lima')->subDays(3);

        $dropoutList = [];

        foreach ($participants as $p) {
            $uId = (int) $p->user_id;
            $candidates = [];

            if (isset($sessionActivities[$uId])) {
                $candidates[] = Carbon::createFromTimestamp($sessionActivities[$uId]);
            }
            if (!empty($xpActivities[$uId])) {
                $candidates[] = Carbon::parse($xpActivities[$uId]);
            }
            if (!empty($pomodoroActivities[$uId])) {
                $candidates[] = Carbon::parse($pomodoroActivities[$uId]);
            }
            if (!empty($habitActivities[$uId])) {
                $candidates[] = Carbon::parse($habitActivities[$uId]);
            }
            if (!empty($p->updated_at)) {
                $candidates[] = Carbon::parse($p->updated_at);
            }
            if (!empty($p->created_at)) {
                $candidates[] = Carbon::parse($p->created_at);
            }

            $lastActiveCarbon = null;
            foreach ($candidates as $c) {
                if ($lastActiveCarbon === null || $c->greaterThan($lastActiveCarbon)) {
                    $lastActiveCarbon = $c;
                }
            }

            if ($lastActiveCarbon !== null) {
                $lastActiveCarbon->timezone('America/Lima');
            }

            if ($lastActiveCarbon === null || $lastActiveCarbon->lessThanOrEqualTo($threeDaysAgo)) {
                $daysInactive = $lastActiveCarbon !== null
                    ? (int) $lastActiveCarbon->diffInDays($nowLima)
                    : 99;

                $dropoutList[] = [
                    'participant_code' => $p->participant_code ?? 'P-UNKNOWN',
                    'alias'            => $p->alias ?? '—',
                    'career'           => $p->career ?? '—',
                    'cycle'            => $p->cycle ? "Ciclo {$p->cycle}" : '—',
                    'current_level'    => $p->current_level ?? 1,
                    'current_streak'   => $p->current_streak ?? 0,
                    'days_inactive'    => $daysInactive,
                    'last_active_at'   => $lastActiveCarbon ? $lastActiveCarbon->format('d/m/Y H:i') : 'Nunca',
                ];
            }
        }

        usort($dropoutList, fn ($a, $b) => $b['days_inactive'] <=> $a['days_inactive']);

        return $dropoutList;
    }
}
