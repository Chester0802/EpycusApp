<?php

declare(strict_types=1);

namespace App\Modules\Shop\Application\UseCases;

use App\Modules\Shop\Domain\Contracts\ShopRepositoryInterface;
use App\Modules\Shop\Infrastructure\Models\RewardRedemptionModel;

final class MarkRewardUsedUseCase
{
    public function __construct(
        private readonly ShopRepositoryInterface $repository,
    ) {}

    public function execute(int $redemptionId, int $userId): RewardRedemptionModel
    {
        return $this->repository->markRedemptionUsed($redemptionId, $userId);
    }
}
