<?php

declare(strict_types=1);

namespace App\Modules\DayPlanner\Application\UseCases;

use App\Modules\DayPlanner\Domain\Contracts\DayPlanRepositoryInterface;
use App\Modules\DayPlanner\Infrastructure\Models\DailyPlanItemModel;
use Exception;

final class UpdateDayPlanItemUseCase
{
    public function __construct(
        private readonly DayPlanRepositoryInterface $repository,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public function execute(int $itemId, int $userId, array $data): DailyPlanItemModel
    {
        $item = $this->repository->findItemByIdAndUser($itemId, $userId);
        if ($item === null) {
            throw new Exception('El ítem no existe o no te pertenece.');
        }

        return $this->repository->updateItem($item, $data);
    }
}
