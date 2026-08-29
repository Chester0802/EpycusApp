<?php

declare(strict_types=1);

namespace App\Modules\Finance\Application\UseCases;

use App\Modules\Finance\Domain\Contracts\FinanceRepositoryInterface;

final class DepositSavingsUseCase
{
    public function __construct(
        private readonly FinanceRepositoryInterface $repository,
    ) {}

    public function execute(int $goalId, int $userId, float $amount): array
    {
        return $this->repository->depositSavings($goalId, $userId, $amount);
    }
}
