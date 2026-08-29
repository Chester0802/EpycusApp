<?php

declare(strict_types=1);

namespace App\Modules\Finance\Application\UseCases;

use App\Modules\Finance\Domain\Contracts\FinanceRepositoryInterface;
use Carbon\Carbon;

final class GetFinanceOverviewUseCase
{
    private const CATEGORY_LABELS = [
        'transporte' => ['label' => '🚌 Transporte / Pasajes', 'icon' => '🚌'],
        'alimentacion' => ['label' => '🍱 Alimentación / Almuerzo', 'icon' => '🍱'],
        'materiales' => ['label' => '📚 Fotocopias y Materiales', 'icon' => '📚'],
        'ocio' => ['label' => '🎮 Salidas y Ocio', 'icon' => '🎮'],
        'suscripciones' => ['label' => '📱 Suscripciones y Software', 'icon' => '📱'],
        'servicios' => ['label' => '💡 Servicios y Celular', 'icon' => '💡'],
        'ingreso_mesada' => ['label' => '💵 Mesada / Apoyo Familiar', 'icon' => '💵'],
        'ingreso_trabajo' => ['label' => '💼 Empleo / Prácticas / Freelance', 'icon' => '💼'],
        'ingreso_otro' => ['label' => '✨ Otro Ingreso', 'icon' => '✨'],
        'otro' => ['label' => '📌 Otros Gastos', 'icon' => '📌'],
    ];

    public function __construct(
        private readonly FinanceRepositoryInterface $repository,
    ) {}

    public function execute(int $userId, ?int $month = null, ?int $year = null): array
    {
        $now = Carbon::now('America/Lima');
        $month = $month ?? $now->month;
        $year = $year ?? $now->year;

        $transactions = $this->repository->getTransactionsForMonth($userId, $month, $year);
        $budgets = $this->repository->getBudgetsForMonth($userId, $month, $year)->keyBy('category');
        $savingsGoals = $this->repository->getSavingsGoals($userId);

        $totalIncome = 0.0;
        $totalExpenses = 0.0;
        $expensesByCategory = [];

        foreach ($transactions as $t) {
            if ($t->type === 'income') {
                $totalIncome += $t->amount;
            } elseif ($t->type === 'expense') {
                $totalExpenses += $t->amount;
                $expensesByCategory[$t->category] = ($expensesByCategory[$t->category] ?? 0.0) + $t->amount;
            }
        }

        $budgetStatus = [];
        foreach ($budgets as $category => $budget) {
            $spent = $expensesByCategory[$category] ?? 0.0;
            $limit = $budget->monthly_limit;
            $percentage = $limit > 0 ? round(($spent / $limit) * 100, 1) : 0;
            $status = $percentage >= 100 ? 'red' : ($percentage >= 75 ? 'yellow' : 'green');

            $budgetStatus[] = [
                'category' => $category,
                'label' => self::CATEGORY_LABELS[$category]['label'] ?? $category,
                'icon' => self::CATEGORY_LABELS[$category]['icon'] ?? '📌',
                'limit' => $limit,
                'spent' => $spent,
                'remaining' => max(0, $limit - $spent),
                'percentage' => min(100, $percentage),
                'raw_percentage' => $percentage,
                'status' => $status,
            ];
        }

        return [
            'month' => $month,
            'year' => $year,
            'summary' => [
                'total_income' => round($totalIncome, 2),
                'total_expenses' => round($totalExpenses, 2),
                'net_balance' => round($totalIncome - $totalExpenses, 2),
                'savings_rate' => $totalIncome > 0 ? round((($totalIncome - $totalExpenses) / $totalIncome) * 100, 1) : 0,
            ],
            'budgets' => $budgetStatus,
            'expenses_by_category' => collect($expensesByCategory)->map(fn ($amount, $cat) => [
                'category' => $cat,
                'label' => self::CATEGORY_LABELS[$cat]['label'] ?? $cat,
                'icon' => self::CATEGORY_LABELS[$cat]['icon'] ?? '📌',
                'amount' => round($amount, 2),
                'percentage' => $totalExpenses > 0 ? round(($amount / $totalExpenses) * 100, 1) : 0,
            ])->values()->sortByDesc('amount')->values()->toArray(),
            'transactions' => $transactions->map(fn ($t) => [
                'id' => $t->id,
                'type' => $t->type,
                'amount' => $t->amount,
                'category' => $t->category,
                'label' => self::CATEGORY_LABELS[$t->category]['label'] ?? $t->category,
                'icon' => self::CATEGORY_LABELS[$t->category]['icon'] ?? '📌',
                'date' => $t->date->toDateString(),
                'notes' => $t->notes,
            ])->values()->toArray(),
            'savings_goals' => $savingsGoals->map(fn ($g) => [
                'id' => $g->id,
                'title' => $g->title,
                'target_amount' => $g->target_amount,
                'current_amount' => $g->current_amount,
                'target_date' => $g->target_date?->toDateString(),
                'reward_xp' => $g->reward_xp,
                'is_completed' => $g->is_completed,
                'progress_percentage' => $g->target_amount > 0 ? min(100, round(($g->current_amount / $g->target_amount) * 100, 1)) : 0,
            ])->values()->toArray(),
        ];
    }
}
