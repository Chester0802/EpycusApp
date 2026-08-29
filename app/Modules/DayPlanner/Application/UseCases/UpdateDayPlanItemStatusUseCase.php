<?php

declare(strict_types=1);

namespace App\Modules\DayPlanner\Application\UseCases;

use App\Modules\DayPlanner\Domain\Contracts\DayPlanRepositoryInterface;
use App\Modules\Gamification\Application\UseCases\AwardXpUseCase;
use Carbon\Carbon;
use Exception;

final class UpdateDayPlanItemStatusUseCase
{
    public function __construct(
        private readonly DayPlanRepositoryInterface $repository,
        private readonly AwardXpUseCase $awardXp,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function execute(
        int $itemId,
        int $userId,
        string $status,
        ?string $skipReason = null,
        ?string $postponeToBlock = null
    ): array {
        $item = $this->repository->findItemByIdAndUser($itemId, $userId);
        if ($item === null) {
            throw new Exception('El ítem del plan diario no existe o no te pertenece.');
        }

        $now = Carbon::now('America/Lima');
        $xpAwarded = 0;
        $coinsAwarded = 0;
        $postponedCount = $item->postponed_count;
        $targetBlock = $item->time_block;

        if ($status === 'done') {
            $baseXp = 15;
            $dailyCap = 25; // hasta 25 acciones diarias
            $result = $this->awardXp->execute(
                userId: $userId,
                sourceType: 'day_plan_item',
                sourceId: $item->id,
                baseXp: $baseXp,
                dailyCap: $dailyCap,
                countsTowardStreak: true,
            );
            $xpAwarded = $result->xpAwarded;
            $coinsAwarded = intdiv($xpAwarded, 10);

            $this->repository->updateItem($item, [
                'status' => 'done',
                'completed_at' => $now,
                'xp_awarded' => $xpAwarded,
                'coins_awarded' => $coinsAwarded,
            ]);
        } elseif ($status === 'skipped') {
            $this->repository->updateItem($item, [
                'status' => 'skipped',
                'skip_reason' => $skipReason ?? 'otro',
                'completed_at' => null,
            ]);
        } elseif ($status === 'postponed') {
            $postponedCount++;
            $nextBlock = $postponeToBlock ?? $this->calculateNextBlock($item->time_block);
            $targetBlock = $nextBlock;

            $this->repository->updateItem($item, [
                'status' => 'postponed',
                'time_block' => $nextBlock,
                'postponed_to_block' => $nextBlock,
                'postponed_count' => $postponedCount,
                'completed_at' => null,
            ]);
        } else {
            // reset to pending
            $this->repository->updateItem($item, [
                'status' => 'pending',
                'skip_reason' => null,
                'postponed_to_block' => null,
                'completed_at' => null,
            ]);
        }

        return [
            'item_id' => $item->id,
            'status' => $status,
            'xp_awarded' => $xpAwarded,
            'coins_awarded' => $coinsAwarded,
            'postponed_count' => $postponedCount,
            'time_block' => $targetBlock,
            'needs_ai_help' => $postponedCount >= 3,
        ];
    }

    private function calculateNextBlock(string $currentBlock): string
    {
        return match ($currentBlock) {
            'morning' => 'afternoon',
            'afternoon' => 'night',
            default => 'night',
        };
    }
}
