<?php

declare(strict_types=1);

namespace App\Modules\Finance\Application\UseCases;

use App\Modules\Finance\Domain\Contracts\FinanceRepositoryInterface;

final class DeleteTransactionUseCase
{
    public function __construct(
        private readonly FinanceRepositoryInterface $repository,
    ) {}

    public function execute(int $transactionId, int $userId): bool
    {
        return $this->repository->deleteTransaction($transactionId, $userId);
    }
}
