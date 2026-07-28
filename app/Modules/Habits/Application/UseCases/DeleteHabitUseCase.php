<?php

declare(strict_types=1);

namespace App\Modules\Habits\Application\UseCases;

use App\Modules\Habits\Domain\Contracts\HabitRepositoryInterface;
use App\Modules\Habits\Domain\Events\HabitDeleted;
use App\Modules\Habits\Infrastructure\Models\HabitModel;
use Illuminate\Contracts\Events\Dispatcher;

final class DeleteHabitUseCase
{
    public function __construct(
        private HabitRepositoryInterface $repository,
        private Dispatcher $events,
    ) {}

    public function execute(HabitModel $habit): bool
    {
        $result = $this->repository->delete($habit);

        $this->events->dispatch(new HabitDeleted(
            habitId: $habit->id,
            userId: $habit->user_id,
            occurredAt: new \DateTimeImmutable,
        ));

        return $result;
    }
}
