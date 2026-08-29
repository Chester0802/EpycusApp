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
        $threeDaysAgo = Carbon::now()->subDays(3);

        return DB::table('participants')
            ->join('users', 'users.id', '=', 'participants.user_id')
            ->leftJoin('user_progress', 'user_progress.user_id', '=', 'users.id')
            ->where('users.role', 'student')
            ->where('users.updated_at', '<=', $threeDaysAgo)
            ->select([
                'participants.participant_code',
                'users.alias',
                'users.career',
                'users.cycle',
                'user_progress.current_level',
                'user_progress.current_streak',
                'users.updated_at as last_active_at',
            ])
            ->orderBy('users.updated_at', 'asc')
            ->get()
            ->map(function ($p) {
                $daysInactive = Carbon::parse($p->last_active_at)->diffInDays(Carbon::now());

                return [
                    'participant_code' => $p->participant_code,
                    'alias'            => $p->alias ?? '—',
                    'career'           => $p->career ?? '—',
                    'cycle'            => $p->cycle ? "Ciclo {$p->cycle}" : '—',
                    'current_level'    => $p->current_level ?? 1,
                    'current_streak'   => $p->current_streak ?? 0,
                    'days_inactive'    => $daysInactive,
                    'last_active_at'   => date('d/m/Y H:i', strtotime($p->last_active_at)),
                ];
            })
            ->toArray();
    }
}
