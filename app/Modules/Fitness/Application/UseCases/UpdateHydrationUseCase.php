<?php

declare(strict_types=1);

namespace App\Modules\Fitness\Application\UseCases;

use App\Modules\Fitness\Domain\Contracts\FitnessRepositoryInterface;

final class UpdateHydrationUseCase
{
    public function __construct(
        private readonly FitnessRepositoryInterface $repository,
    ) {}

    public function execute(int $userId, string $date, int $delta): array
    {
        return $this->repository->updateHydrationGlasses($userId, $date, $delta);
    }
}
