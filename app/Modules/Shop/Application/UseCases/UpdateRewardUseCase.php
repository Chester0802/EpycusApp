<?php

declare(strict_types=1);

namespace App\Modules\Shop\Application\UseCases;

use App\Modules\Shop\Domain\Contracts\ShopRepositoryInterface;
use App\Modules\Shop\Infrastructure\Models\CustomRewardModel;

final class UpdateRewardUseCase
{
    public function __construct(
        private readonly ShopRepositoryInterface $repository,
    ) {}

    public function execute(int $rewardId, int $userId, array $data): CustomRewardModel
    {
        return $this->repository->updateReward($rewardId, $userId, $data);
    }
}
