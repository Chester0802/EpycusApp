<?php

declare(strict_types=1);

namespace App\Modules\Shop\Application\UseCases;

use App\Modules\Gamification\Infrastructure\Models\UserProgressModel;
use App\Modules\Shop\Domain\Contracts\ShopRepositoryInterface;
use Exception;
use Illuminate\Support\Facades\DB;

final class RedeemRewardUseCase
{
    public function __construct(
        private readonly ShopRepositoryInterface $repository,
    ) {}

    public function execute(int $userId, int $rewardId): array
    {
        return DB::transaction(function () use ($userId, $rewardId) {
            $reward = $this->repository->findReward($rewardId, $userId);
            if (! $reward) {
                throw new Exception('Recompensa no encontrada.');
            }

            /** @var UserProgressModel|null $progress */
            $progress = UserProgressModel::where('user_id', $userId)->lockForUpdate()->first();
            $currentCoins = $progress?->coins ?? 0;

            if ($currentCoins < $reward->cost_coins) {
                throw new Exception("Monedas insuficientes. Tienes {$currentCoins} monedas y necesitas {$reward->cost_coins}.");
            }

            // Descontar monedas
            $progress->coins = max(0, $currentCoins - $reward->cost_coins);
            $progress->save();

            // Registrar canje
            $redemption = $this->repository->createRedemption($userId, [
                'reward_id' => $reward->id,
                'title' => $reward->title,
                'cost_coins' => $reward->cost_coins,
                'icon' => $reward->icon,
            ]);

            return [
                'success' => true,
                'message' => "¡Canje exitoso! Disfruta: {$reward->title}",
                'new_balance' => $progress->coins,
                'redemption_id' => $redemption->id,
            ];
        });
    }
}
