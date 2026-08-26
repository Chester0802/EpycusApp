<?php

declare(strict_types=1);

namespace App\Modules\Shop\Application\UseCases;

use App\Modules\Shop\Domain\Contracts\ShopRepositoryInterface;

final class DeleteRewardUseCase
{
    public function __construct(
        private readonly ShopRepositoryInterface $repository,
    ) {}

    public function execute(int $rewardId, int $userId): bool
    {
        return $this->repository->deleteReward($rewardId, $userId);
    }
}
