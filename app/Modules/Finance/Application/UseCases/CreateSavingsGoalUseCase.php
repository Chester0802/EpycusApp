<?php

declare(strict_types=1);

namespace App\Modules\Finance\Application\UseCases;

use App\Modules\Finance\Domain\Contracts\FinanceRepositoryInterface;
use App\Modules\Finance\Infrastructure\Models\FinanceSavingsGoalModel;

final class CreateSavingsGoalUseCase
{
    public function __construct(
        private readonly FinanceRepositoryInterface $repository,
    ) {}

    public function execute(int $userId, array $data): FinanceSavingsGoalModel
    {
        return $this->repository->createSavingsGoal($userId, $data);
    }
}
