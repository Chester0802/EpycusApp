<?php

declare(strict_types=1);

namespace App\Modules\Habits\Domain\Contracts;

use App\Modules\Habits\Infrastructure\Models\HabitCompletionModel;
use App\Modules\Habits\Infrastructure\Models\HabitModel;
use Illuminate\Support\Collection;

interface HabitRepositoryInterface
{
    /**
     * @return Collection<int, HabitModel>
     */
    public function getActiveForUser(int $userId): Collection;

    /**
     * @return Collection<int, HabitModel>
     */
    public function getArchivedForUser(int $userId): Collection;

    public function findByIdAndUser(int $habitId, int $userId): ?HabitModel;

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): HabitModel;

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(HabitModel $habit, array $data): HabitModel;

    public function delete(HabitModel $habit): bool;

    public function completeForDate(HabitModel $habit, string $date, bool $isLate = false): HabitCompletionModel;

    public function uncompleteForDate(HabitModel $habit, string $date): bool;

    public function isCompletedForDate(int $habitId, string $date): bool;
}
