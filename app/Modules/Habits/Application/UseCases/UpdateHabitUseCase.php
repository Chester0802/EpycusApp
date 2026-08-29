<?php

declare(strict_types=1);

namespace App\Modules\Habits\Application\UseCases;

use App\Modules\Habits\Application\DTOs\UpdateHabitDTO;
use App\Modules\Habits\Domain\Contracts\HabitRepositoryInterface;
use App\Modules\Habits\Domain\Events\HabitUpdated;
use App\Modules\Habits\Infrastructure\Models\HabitModel;
use Illuminate\Contracts\Events\Dispatcher;

final class UpdateHabitUseCase
{
    public function __construct(
        private HabitRepositoryInterface $repository,
        private Dispatcher $events,
    ) {}

    public function execute(UpdateHabitDTO $dto): HabitModel
    {
        $habit = $this->repository->findByIdAndUser($dto->habitId, $dto->userId);

        if ($habit === null) {
            throw new \RuntimeException('Hábito no encontrado.');
        }

        $habit = $this->repository->update($habit, [
            'title' => $dto->title,
            'category' => $dto->category,
            'frequency' => $dto->frequency,
            'icon' => $dto->icon,
            'time_of_day' => $dto->timeOfDay,
            'cue_trigger' => $dto->cueTrigger,
        ]);

        $this->events->dispatch(new HabitUpdated(
            habitId: $habit->id,
            userId: $dto->userId,
            category: $dto->category,
            occurredAt: new \DateTimeImmutable,
        ));

        return $habit;
    }
}
