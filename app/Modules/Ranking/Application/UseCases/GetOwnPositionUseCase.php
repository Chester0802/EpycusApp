<?php

declare(strict_types=1);

namespace App\Modules\Ranking\Application\UseCases;

final class GetOwnPositionUseCase
{
    public function execute(array $ranking, int $userId): array
    {
        $totalParticipants = count($ranking);
        $ownRank = null;

        foreach ($ranking as $item) {
            if ($item['user_id'] === $userId) {
                $ownRank = $item;
                break;
            }
        }

        if (! $ownRank) {
            return [
                'rank' => $totalParticipants + 1,
                'total_participants' => $totalParticipants,
                'level' => 1,
                'total_xp' => 0,
                'current_streak' => 0,
            ];
        }

        return array_merge($ownRank, [
            'total_participants' => $totalParticipants,
        ]);
    }
}
