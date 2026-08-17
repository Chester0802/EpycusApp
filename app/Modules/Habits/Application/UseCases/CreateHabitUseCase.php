<?php

declare(strict_types=1);

namespace App\Modules\Habits\Application\UseCases;

use App\Modules\Habits\Application\DTOs\CreateHabitDTO;
use App\Modules\Habits\Domain\Contracts\HabitRepositoryInterface;
use App\Modules\Habits\Domain\Events\HabitCreated;
use App\Modules\Habits\Infrastructure\Models\HabitModel;
use Illuminate\Contracts\Events\Dispatcher;

final class CreateHabitUseCase
{
    public function __construct(
        private HabitRepositoryInterface $repository,
        private Dispatcher $events,
    ) {}

    public function execute(CreateHabitDTO $dto): HabitModel
    {
        $habit = $this->repository->create([
            'user_id' => $dto->userId,
            'title' => $dto->title,
            'category' => $dto->category,
            'frequency' => $dto->frequency,
            'icon' => $dto->icon,
            'time_of_day' => $dto->timeOfDay,
            'cue_trigger' => $dto->cueTrigger,
            'is_active' => true,
        ]);

        $this->events->dispatch(new HabitCreated(
            habitId: $habit->id,
            userId: $dto->userId,
            category: $dto->category,
            occurredAt: new \DateTimeImmutable,
        ));

        return $habit;
    }
}
