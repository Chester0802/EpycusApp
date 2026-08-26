<?php

declare(strict_types=1);

namespace App\Modules\Finance\Application\UseCases;

use App\Modules\Finance\Domain\Contracts\FinanceRepositoryInterface;
use App\Modules\Finance\Infrastructure\Models\FinanceBudgetModel;

final class SetBudgetUseCase
{
    public function __construct(
        private readonly FinanceRepositoryInterface $repository,
    ) {}

    public function execute(int $userId, int $month, int $year, string $category, float $monthlyLimit): FinanceBudgetModel
    {
        return $this->repository->setBudget($userId, $month, $year, $category, $monthlyLimit);
    }
}
