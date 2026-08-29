<?php

declare(strict_types=1);

namespace App\Modules\Shop\Infrastructure\Repositories;

use App\Modules\Shop\Domain\Contracts\ShopRepositoryInterface;
use App\Modules\Shop\Infrastructure\Models\CustomRewardModel;
use App\Modules\Shop\Infrastructure\Models\RewardRedemptionModel;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Collection;

final class EloquentShopRepository implements ShopRepositoryInterface
{
    public function getActiveRewards(int $userId): Collection
    {
        return CustomRewardModel::where('user_id', $userId)
            ->where('is_active', true)
            ->orderBy('cost_coins', 'asc')
            ->get();
    }

    public function findReward(int $rewardId, int $userId): ?CustomRewardModel
    {
        return CustomRewardModel::where('id', $rewardId)
            ->where('user_id', $userId)
            ->first();
    }

    public function createReward(int $userId, array $data): CustomRewardModel
    {
        return CustomRewardModel::create([
            'user_id' => $userId,
            'title' => $data['title'],
            'cost_coins' => (int) ($data['cost_coins'] ?? 100),
            'icon' => $data['icon'] ?? '🎁',
            'category' => $data['category'] ?? 'ocio',
            'is_active' => true,
        ]);
    }

    public function updateReward(int $rewardId, int $userId, array $data): CustomRewardModel
    {
        $reward = $this->findReward($rewardId, $userId);
        if (! $reward) {
            throw new Exception('Recompensa no encontrada.');
        }

        $reward->update([
            'title' => $data['title'] ?? $reward->title,
            'cost_coins' => isset($data['cost_coins']) ? (int) $data['cost_coins'] : $reward->cost_coins,
            'icon' => $data['icon'] ?? $reward->icon,
            'category' => $data['category'] ?? $reward->category,
        ]);

        return $reward;
    }

    public function deleteReward(int $rewardId, int $userId): bool
    {
        $reward = $this->findReward($rewardId, $userId);
        if (! $reward) {
            throw new Exception('Recompensa no encontrada.');
        }

        return (bool) $reward->delete();
    }

    public function getRedemptions(int $userId, int $limit = 20): Collection
    {
        return RewardRedemptionModel::where('user_id', $userId)
            ->orderBy('id', 'desc')
            ->limit($limit)
            ->get();
    }

    public function createRedemption(int $userId, array $data): RewardRedemptionModel
    {
        return RewardRedemptionModel::create([
            'user_id' => $userId,
            'reward_id' => $data['reward_id'] ?? null,
            'title' => $data['title'],
            'cost_coins' => (int) $data['cost_coins'],
            'icon' => $data['icon'] ?? '🎁',
            'status' => 'redeemed',
            'redeemed_at' => Carbon::now(),
        ]);
    }

    public function markRedemptionUsed(int $redemptionId, int $userId): RewardRedemptionModel
    {
        $redemption = RewardRedemptionModel::where('id', $redemptionId)
            ->where('user_id', $userId)
            ->first();

        if (! $redemption) {
            throw new Exception('Canje no encontrado.');
        }

        $redemption->update([
            'status' => 'used',
            'used_at' => Carbon::now(),
        ]);

        return $redemption;
    }
}
