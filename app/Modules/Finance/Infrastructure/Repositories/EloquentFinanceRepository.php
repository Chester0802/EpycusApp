<?php

declare(strict_types=1);

namespace App\Modules\Finance\Infrastructure\Repositories;

use App\Modules\Finance\Domain\Contracts\FinanceRepositoryInterface;
use App\Modules\Finance\Infrastructure\Models\FinanceBudgetModel;
use App\Modules\Finance\Infrastructure\Models\FinanceSavingsGoalModel;
use App\Modules\Finance\Infrastructure\Models\FinanceTransactionModel;
use App\Modules\Gamification\Application\UseCases\AwardXpUseCase;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class EloquentFinanceRepository implements FinanceRepositoryInterface
{
    public function __construct(
        private readonly AwardXpUseCase $awardXp,
    ) {}

    public function getTransactionsForMonth(int $userId, int $month, int $year): Collection
    {
        $start = Carbon::createFromDate($year, $month, 1)->startOfMonth()->toDateString();
        $end = Carbon::createFromDate($year, $month, 1)->endOfMonth()->toDateString();

        return FinanceTransactionModel::where('user_id', $userId)
            ->whereBetween('date', [$start, $end])
            ->orderBy('date', 'desc')
            ->orderBy('id', 'desc')
            ->get();
    }

    public function createTransaction(int $userId, array $data): FinanceTransactionModel
    {
        return FinanceTransactionModel::create([
            'user_id' => $userId,
            'type' => $data['type'],
            'amount' => (float) $data['amount'],
            'category' => $data['category'],
            'date' => $data['date'] ?? Carbon::now()->toDateString(),
            'notes' => $data['notes'] ?? null,
        ]);
    }

    public function deleteTransaction(int $transactionId, int $userId): bool
    {
        $transaction = FinanceTransactionModel::where('id', $transactionId)
            ->where('user_id', $userId)
            ->first();

        if (! $transaction) {
            throw new Exception('Transacción no encontrada.');
        }

        return (bool) $transaction->delete();
    }

    public function getBudgetsForMonth(int $userId, int $month, int $year): Collection
    {
        return FinanceBudgetModel::where('user_id', $userId)
            ->where('month', $month)
            ->where('year', $year)
            ->get();
    }

    public function setBudget(int $userId, int $month, int $year, string $category, float $monthlyLimit): FinanceBudgetModel
    {
        return FinanceBudgetModel::updateOrCreate(
            [
                'user_id' => $userId,
                'month' => $month,
                'year' => $year,
                'category' => $category,
            ],
            [
                'monthly_limit' => $monthlyLimit,
            ]
        );
    }

    public function getSavingsGoals(int $userId): Collection
    {
        return FinanceSavingsGoalModel::where('user_id', $userId)
            ->orderBy('is_completed', 'asc')
            ->orderBy('id', 'desc')
            ->get();
    }

    public function createSavingsGoal(int $userId, array $data): FinanceSavingsGoalModel
    {
        return FinanceSavingsGoalModel::create([
            'user_id' => $userId,
            'title' => $data['title'],
            'target_amount' => (float) $data['target_amount'],
            'current_amount' => (float) ($data['current_amount'] ?? 0),
            'target_date' => $data['target_date'] ?? null,
            'reward_xp' => (int) ($data['reward_xp'] ?? 100),
            'is_completed' => false,
        ]);
    }

    public function depositSavings(int $goalId, int $userId, float $amount): array
    {
        return DB::transaction(function () use ($goalId, $userId, $amount) {
            /** @var FinanceSavingsGoalModel|null $goal */
            $goal = FinanceSavingsGoalModel::where('id', $goalId)
                ->where('user_id', $userId)
                ->lockForUpdate()
                ->first();

            if (! $goal) {
                throw new Exception('Meta de ahorro no encontrada.');
            }

            $goal->current_amount = max(0, $goal->current_amount + $amount);
            $wasCompleted = $goal->is_completed;
            $justCompleted = false;
            $xpAwarded = 0;

            if ($goal->current_amount >= $goal->target_amount && ! $wasCompleted) {
                $goal->is_completed = true;
                $justCompleted = true;
                $xpAwarded = $goal->reward_xp;
                $this->awardXp->execute($userId, 'savings_goal', $goal->id, $xpAwarded, 10, false);
            }

            $goal->save();

            return [
                'goal' => $goal,
                'just_completed' => $justCompleted,
                'xp_awarded' => $xpAwarded,
            ];
        });
    }

    public function deleteSavingsGoal(int $goalId, int $userId): bool
    {
        $goal = FinanceSavingsGoalModel::where('id', $goalId)
            ->where('user_id', $userId)
            ->first();

        if (! $goal) {
            throw new Exception('Meta de ahorro no encontrada.');
        }

        return (bool) $goal->delete();
    }
}
