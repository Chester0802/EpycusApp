<?php

declare(strict_types=1);

namespace App\Modules\Habits\Application\UseCases;

use App\Modules\Habits\Domain\Contracts\HabitRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

final class ToggleHabitCompletionUseCase
{
    public function __construct(private HabitRepositoryInterface $repository) {}

    /**
     * @return array{completed: bool, xp_awarded: int}
     */
    public function execute(int $habitId, int $userId, ?string $date = null): array
    {
        $habit = $this->repository->findByIdAndUser($habitId, $userId);

        if (! $habit) {
            throw ValidationException::withMessages([
                'habit' => 'El hábito no existe o no pertenece al usuario.',
            ]);
        }

        $targetDate = $date ?? Carbon::now()->toDateString();
        $isCompleted = $this->repository->isCompletedForDate($habitId, $targetDate);

        if ($isCompleted) {
            $this->repository->uncompleteForDate($habit, $targetDate);

            return [
                'completed' => false,
                'xp_awarded' => 0,
            ];
        }

        $isLate = Carbon::parse($targetDate)->lt(Carbon::now()->startOfDay());
        $xpAwarded = $isLate ? 5 : 10;

        $this->repository->completeForDate($habit, $targetDate, $xpAwarded, $isLate);

        return [
            'completed' => true,
            'xp_awarded' => $xpAwarded,
        ];
    }
}
