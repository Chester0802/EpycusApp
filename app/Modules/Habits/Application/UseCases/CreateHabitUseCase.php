<?php

declare(strict_types=1);

namespace App\Modules\Habits\Application\UseCases;

use App\Modules\Habits\Application\DTOs\CreateHabitDTO;
use App\Modules\Habits\Domain\Contracts\HabitRepositoryInterface;
use App\Modules\Habits\Infrastructure\Models\HabitModel;

final class CreateHabitUseCase
{
    public function __construct(private HabitRepositoryInterface $repository) {}

    public function execute(CreateHabitDTO $dto): HabitModel
    {
        return $this->repository->create([
            'user_id' => $dto->userId,
            'title' => $dto->title,
            'category' => $dto->category,
            'frequency' => $dto->frequency,
            'icon' => $dto->icon,
            'is_active' => true,
        ]);
    }
}
