<?php

declare(strict_types=1);

namespace App\Modules\DayPlanner\Application\UseCases;

use App\Modules\DayPlanner\Domain\Contracts\DayPlanRepositoryInterface;
use App\Modules\DayPlanner\Infrastructure\Models\DailyPlanItemModel;

final class CreateDayPlanItemUseCase
{
    public function __construct(
        private readonly DayPlanRepositoryInterface $repository,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public function execute(int $userId, array $data): DailyPlanItemModel
    {
        return $this->repository->createItem(array_merge($data, [
            'user_id' => $userId,
            'status' => 'pending',
        ]));
    }
}
