<?php

declare(strict_types=1);

namespace App\Modules\Ranking\Application\UseCases;

use App\Shared\Domain\Services\AvatarAssetResolver;
use Illuminate\Support\Facades\DB;

final class GetGlobalRankingUseCase
{
    public function __construct(
        private readonly AvatarAssetResolver $avatars,
    ) {}

    public function execute(): array
    {
        $rows = DB::table('users')
            ->leftJoin('user_progress', 'user_progress.user_id', '=', 'users.id')
            ->select([
                'users.id as user_id',
                'users.name',
                'users.avatar_style',
                'users.avatar_gender',
                'user_progress.current_level',
                'user_progress.current_phase',
                'user_progress.total_xp',
                'user_progress.current_streak',
                'user_progress.coins',
            ])
            ->get();

        $sorted = $rows->map(function ($row) {
            return [
                'user_id' => (int) $row->user_id,
                'name' => $row->name,
                'avatar_style' => $row->avatar_style,
                'avatar_gender' => $row->avatar_gender,
                'level' => (int) ($row->current_level ?? 1),
                'phase' => (int) ($row->current_phase ?? 1),
                'total_xp' => (int) ($row->total_xp ?? 0),
                'current_streak' => (int) ($row->current_streak ?? 0),
                'coins' => (int) ($row->coins ?? 0),
            ];
        })->sortBy([
            ['total_xp', 'desc'],
            ['current_streak', 'desc'],
            ['user_id', 'asc'],
        ])->values();

        $ranking = [];
        $rank = 1;

        foreach ($sorted as $row) {
            $avatar = $this->avatars->imageForModule($row['avatar_style'], $row['avatar_gender'], 'dashboard');
            $ranking[] = [
                'rank' => $rank,
                'user_id' => $row['user_id'],
                'name' => $row['name'],
                'level' => $row['level'],
                'phase' => $row['phase'],
                'total_xp' => $row['total_xp'],
                'current_streak' => $row['current_streak'],
                'coins' => $row['coins'],
                'avatar_image' => $avatar,
            ];
            $rank++;
        }

        return $ranking;
    }
}
