<?php

declare(strict_types=1);

namespace App\Modules\Shop\Domain\Contracts;

use App\Modules\Shop\Infrastructure\Models\CustomRewardModel;
use App\Modules\Shop\Infrastructure\Models\RewardRedemptionModel;
use Illuminate\Support\Collection;

interface ShopRepositoryInterface
{
    /**
     * @return Collection<int, CustomRewardModel>
     */
    public function getActiveRewards(int $userId): Collection;

    public function findReward(int $rewardId, int $userId): ?CustomRewardModel;

    public function createReward(int $userId, array $data): CustomRewardModel;

    public function updateReward(int $rewardId, int $userId, array $data): CustomRewardModel;

    public function deleteReward(int $rewardId, int $userId): bool;

    /**
     * @return Collection<int, RewardRedemptionModel>
     */
    public function getRedemptions(int $userId, int $limit = 20): Collection;

    public function createRedemption(int $userId, array $data): RewardRedemptionModel;

    public function markRedemptionUsed(int $redemptionId, int $userId): RewardRedemptionModel;
}
