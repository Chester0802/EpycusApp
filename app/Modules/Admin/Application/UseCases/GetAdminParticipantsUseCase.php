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
        return DB::table('participants')
            ->join('users', 'users.id', '=', 'participants.user_id')
            ->leftJoin('user_progress', 'user_progress.user_id', '=', 'users.id')
            ->select([
                'participants.participant_code',
                'user_progress.current_level',
                'user_progress.current_phase',
                'user_progress.total_xp',
                'user_progress.current_streak',
                'users.updated_at as last_active_at',
            ])
            ->orderBy('user_progress.total_xp', 'desc')
            ->get()
            ->map(fn ($p) => [
                'participant_code' => $p->participant_code ?? 'P-UNKNOWN',
                'current_level' => $p->current_level ?? 1,
                'current_phase' => $p->current_phase ?? 1,
                'total_xp' => $p->total_xp ?? 0,
                'current_streak' => $p->current_streak ?? 0,
                'last_active_at' => $p->last_active_at ? date('d/m/Y H:i', strtotime($p->last_active_at)) : 'Nunca',
            ])
            ->toArray();
    }
}
