<?php

declare(strict_types=1);

namespace App\Modules\Finance\Domain\Contracts;

use App\Modules\Finance\Infrastructure\Models\FinanceBudgetModel;
use App\Modules\Finance\Infrastructure\Models\FinanceSavingsGoalModel;
use App\Modules\Finance\Infrastructure\Models\FinanceTransactionModel;
use Illuminate\Support\Collection;

interface FinanceRepositoryInterface
{
    /**
     * @return Collection<int, FinanceTransactionModel>
     */
    public function getTransactionsForMonth(int $userId, int $month, int $year): Collection;

    public function createTransaction(int $userId, array $data): FinanceTransactionModel;

    public function deleteTransaction(int $transactionId, int $userId): bool;

    /**
     * @return Collection<int, FinanceBudgetModel>
     */
    public function getBudgetsForMonth(int $userId, int $month, int $year): Collection;

    public function setBudget(int $userId, int $month, int $year, string $category, float $monthlyLimit): FinanceBudgetModel;

    /**
     * @return Collection<int, FinanceSavingsGoalModel>
     */
    public function getSavingsGoals(int $userId): Collection;

    public function createSavingsGoal(int $userId, array $data): FinanceSavingsGoalModel;

    public function depositSavings(int $goalId, int $userId, float $amount): array;

    public function deleteSavingsGoal(int $goalId, int $userId): bool;
}
