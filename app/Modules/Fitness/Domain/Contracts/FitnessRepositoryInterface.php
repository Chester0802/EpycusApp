<?php

declare(strict_types=1);

namespace App\Modules\Fitness\Domain\Contracts;

use App\Modules\Fitness\Infrastructure\Models\DailyHydrationLogModel;
use App\Modules\Fitness\Infrastructure\Models\FitnessExerciseModel;
use App\Modules\Fitness\Infrastructure\Models\FitnessWorkoutLogModel;
use Illuminate\Support\Collection;

interface FitnessRepositoryInterface
{
    /**
     * @return Collection<int, FitnessExerciseModel>
     */
    public function getAllExercises(): Collection;

    public function seedDefaultExercisesIfEmpty(): void;

    /**
     * @return Collection<int, FitnessWorkoutLogModel>
     */
    public function getWorkoutLogsForUser(int $userId, int $limit = 15): Collection;

    public function logWorkout(int $userId, array $data): array;

    public function getHydrationForDate(int $userId, string $date): DailyHydrationLogModel;

    public function updateHydrationGlasses(int $userId, string $date, int $delta): array;
}
