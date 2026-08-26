<?php

declare(strict_types=1);

namespace App\Modules\Finance\Application\UseCases;

use App\Modules\Finance\Domain\Contracts\FinanceRepositoryInterface;

final class DeleteSavingsGoalUseCase
{
    public function __construct(
        private readonly FinanceRepositoryInterface $repository,
    ) {}

    public function execute(int $goalId, int $userId): bool
    {
        return $this->repository->deleteSavingsGoal($goalId, $userId);
    }
}
