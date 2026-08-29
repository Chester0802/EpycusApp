<?php

declare(strict_types=1);

namespace App\Modules\Habits\Application\UseCases;

use App\Modules\Habits\Domain\Contracts\HabitRepositoryInterface;
use App\Modules\Habits\Domain\Events\HabitCompleted;
use App\Modules\Habits\Domain\Events\HabitUncompleted;
use Carbon\Carbon;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Validation\ValidationException;

/**
 * Deliberadamente no sabe nada de XP. Antes calculaba `$isLate ? 5 : 10` acá
 * mismo, duplicando una regla que le pertenece a Gamification
 * (docs/03-GAMIFICACION.md §3, `config/gamification.php`) — cualquier ajuste
 * de esos valores obligaba a tocar este módulo, exactamente lo que
 * "la gamificación protege el dato" (docs/03-GAMIFICACION.md §1) busca
 * evitar. Ahora solo emite `HabitCompleted`/`HabitUncompleted`; quien
 * escucha y decide cuánto XP otorgar (con tope diario y multiplicador de
 * racha) es `Gamification\Application\Listeners\AwardXpFromHabitListener`.
 */
final class ToggleHabitCompletionUseCase
{
    public function __construct(
        private HabitRepositoryInterface $repository,
        private Dispatcher $events,
    ) {}

    /**
     * @return array{completed: bool}
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

            $this->events->dispatch(new HabitUncompleted(
                habitId: $habitId,
                userId: $userId,
                completedFor: $targetDate,
                occurredAt: new \DateTimeImmutable,
            ));

            return ['completed' => false];
        }

        $isLate = Carbon::parse($targetDate)->lt(Carbon::now()->startOfDay());

        $this->repository->completeForDate($habit, $targetDate, $isLate);

        // Síncrono a propósito: quien escucha este evento (Gamification)
        // otorga el XP en la misma petición, no en cola — así el controller
        // puede reportar el XP real ya otorgado (ver HabitsController::toggle()).
        $this->events->dispatch(new HabitCompleted(
            habitId: $habitId,
            userId: $userId,
            completedFor: $targetDate,
            isLate: $isLate,
            occurredAt: new \DateTimeImmutable,
        ));

        return ['completed' => true];
    }
}
