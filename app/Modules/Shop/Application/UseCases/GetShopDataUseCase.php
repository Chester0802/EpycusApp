<?php

declare(strict_types=1);

namespace App\Modules\Shop\Application\UseCases;

use App\Modules\Gamification\Infrastructure\Models\UserProgressModel;
use App\Modules\Shop\Domain\Contracts\ShopRepositoryInterface;

final class GetShopDataUseCase
{
    private const STARTER_REWARDS = [
        ['title' => '☕ Café especial o postre favorito', 'cost_coins' => 150, 'icon' => '☕', 'category' => 'comida'],
        ['title' => '🎬 Ver 1 episodio de serie o anime', 'cost_coins' => 200, 'icon' => '🎬', 'category' => 'ocio'],
        ['title' => '🎮 1 hora de videojuegos sin culpa', 'cost_coins' => 300, 'icon' => '🎮', 'category' => 'ocio'],
        ['title' => '🍕 Noche de snacks o comida favorita', 'cost_coins' => 350, 'icon' => '🍕', 'category' => 'comida'],
        ['title' => '🌳 Tarde libre de descanso absoluto', 'cost_coins' => 500, 'icon' => '🌳', 'category' => 'descanso'],
    ];

    public function __construct(
        private readonly ShopRepositoryInterface $repository,
    ) {}

    public function execute(int $userId): array
    {
        $rewards = $this->repository->getActiveRewards($userId);

        if ($rewards->isEmpty()) {
            foreach (self::STARTER_REWARDS as $item) {
                $this->repository->createReward($userId, $item);
            }
            $rewards = $this->repository->getActiveRewards($userId);
        }

        $progress = UserProgressModel::find($userId);
        $coins = $progress?->coins ?? 0;

        $redemptions = $this->repository->getRedemptions($userId, 20);

        return [
            'coins' => $coins,
            'rewards' => $rewards->map(fn ($r) => [
                'id' => $r->id,
                'title' => $r->title,
                'cost_coins' => $r->cost_coins,
                'icon' => $r->icon,
                'category' => $r->category,
                'can_afford' => $coins >= $r->cost_coins,
            ])->values()->toArray(),
            'redemptions' => $redemptions->map(fn ($red) => [
                'id' => $red->id,
                'title' => $red->title,
                'reward_type' => $red->reward_type ?? 'catalog',
                'entertainment_title' => $red->entertainment_title,
                'entertainment_category' => $red->entertainment_category,
                'cost_coins' => $red->cost_coins,
                'icon' => $red->icon,
                'status' => $red->status,
                'review_text' => $red->review_text,
                'rating' => $red->rating,
                'redeemed_at' => $red->redeemed_at->format('Y-m-d H:i'),
                'used_at' => $red->used_at?->format('Y-m-d H:i'),
            ])->values()->toArray(),
            'templates' => [
                ['title' => '🎧 Comprar álbum o canción favorita', 'cost_coins' => 250, 'icon' => '🎧', 'category' => 'ocio'],
                ['title' => '🛍️ Auto-regalo o compra deseada', 'cost_coins' => 800, 'icon' => '🛍️', 'category' => 'ocio'],
                ['title' => '🍣 Salida a cenar con amigos', 'cost_coins' => 600, 'icon' => '🍣', 'category' => 'social'],
                ['title' => '😴 Día completo sin alarmas', 'cost_coins' => 450, 'icon' => '😴', 'category' => 'descanso'],
            ],
        ];
    }
}
