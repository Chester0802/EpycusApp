<?php

declare(strict_types=1);

namespace App\Modules\DayPlanner\Application\UseCases;

use App\Modules\DayPlanner\Domain\Contracts\DayPlanRepositoryInterface;
use Exception;

final class DeleteDayPlanItemUseCase
{
    public function __construct(
        private readonly DayPlanRepositoryInterface $repository,
    ) {}

    public function execute(int $itemId, int $userId): bool
    {
        $item = $this->repository->findItemByIdAndUser($itemId, $userId);
        if ($item === null) {
            throw new Exception('El ítem no existe o no te pertenece.');
        }

        return $this->repository->deleteItem($item);
    }
}
