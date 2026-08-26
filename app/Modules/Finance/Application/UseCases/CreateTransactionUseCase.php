<?php

declare(strict_types=1);

namespace App\Modules\Finance\Application\UseCases;

use App\Modules\Finance\Domain\Contracts\FinanceRepositoryInterface;
use App\Modules\Finance\Infrastructure\Models\FinanceBudgetModel;
use App\Modules\Finance\Infrastructure\Models\FinanceSavingsGoalModel;
use App\Modules\Finance\Infrastructure\Models\FinanceTransactionModel;

final class CreateTransactionUseCase
{
    public function __construct(
        private readonly FinanceRepositoryInterface $repository,
    ) {}

    public function execute(int $userId, array $data): FinanceTransactionModel
    {
        return $this->repository->createTransaction($userId, $data);
    }
}
