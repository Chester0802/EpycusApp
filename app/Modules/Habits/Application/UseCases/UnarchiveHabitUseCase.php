<?php

declare(strict_types=1);

namespace App\Modules\Habits\Application\UseCases;

use App\Modules\Habits\Domain\Contracts\HabitRepositoryInterface;
use App\Modules\Habits\Domain\Events\HabitUnarchived;
use App\Modules\Habits\Infrastructure\Models\HabitModel;
use Illuminate\Contracts\Events\Dispatcher;

final class UnarchiveHabitUseCase
{
    public function __construct(
        private HabitRepositoryInterface $repository,
        private Dispatcher $events,
    ) {}

    public function execute(HabitModel $habit): void
    {
        $this->repository->update($habit, ['is_active' => true]);

        $this->events->dispatch(new HabitUnarchived(
            habitId: $habit->id,
            userId: $habit->user_id,
            occurredAt: new \DateTimeImmutable,
        ));
    }
}
