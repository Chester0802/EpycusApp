<?php

declare(strict_types=1);

namespace App\Modules\Fitness\Application\UseCases;

use App\Modules\Fitness\Domain\Contracts\FitnessRepositoryInterface;

final class LogWorkoutUseCase
{
    public function __construct(
        private readonly FitnessRepositoryInterface $repository,
    ) {}

    public function execute(int $userId, array $data): array
    {
        return $this->repository->logWorkout($userId, $data);
    }
}
