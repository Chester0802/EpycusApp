<?php

declare(strict_types=1);

namespace App\Modules\DayPlanner\Application\UseCases;

use App\Modules\DayPlanner\Domain\Contracts\DayPlanRepositoryInterface;
use App\Modules\DayPlanner\Infrastructure\Models\DailyPlanItemModel;
use App\Modules\DayPlanner\Infrastructure\Models\DailyRoutineModel;
use Carbon\Carbon;
use Illuminate\Support\Collection;

final class GetOrGenerateDailyPlanUseCase
{
    public function __construct(
        private readonly DayPlanRepositoryInterface $repository,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function execute(int $userId, ?string $date = null): array
    {
        $planDate = $date ?? Carbon::now('America/Lima')->toDateString();
        $carbonDate = Carbon::parse($planDate, 'America/Lima');
        $dayOfWeek = $carbonDate->dayOfWeekIso; // 1 (Mon) to 7 (Sun)

        $items = $this->repository->getPlanItemsForDate($userId, $planDate);

        // Si no hay ítems creados para esta fecha, auto-generar desde las rutinas activas
        if ($items->isEmpty()) {
            $routines = $this->repository->getActiveRoutinesForUser($userId);

            // Si es un usuario nuevo sin rutinas, crear un set inicial de rutinas recomendadas
            if ($routines->isEmpty()) {
                $this->seedInitialRoutines($userId);
                $routines = $this->repository->getActiveRoutinesForUser($userId);
            }

            foreach ($routines as $routine) {
                // Verificar si la rutina aplica para el día de la semana actual
                $days = $routine->days_of_week ?? [1, 2, 3, 4, 5, 6, 7];
                if (! in_array($dayOfWeek, $days, true)) {
                    continue;
                }

                $this->repository->createItem([
                    'user_id' => $userId,
                    'plan_date' => $planDate,
                    'routine_id' => $routine->id,
                    'title' => $routine->title,
                    'category' => $routine->category,
                    'time_block' => $routine->time_block,
                    'scheduled_time' => $routine->scheduled_time,
                    'estimated_minutes' => $routine->estimated_minutes,
                    'status' => 'pending',
                    'sort_order' => $routine->sort_order,
                ]);
            }

            $items = $this->repository->getPlanItemsForDate($userId, $planDate);
        }

        $allRoutines = $this->repository->getActiveRoutinesForUser($userId);

        return $this->formatPlanResponse($items, $allRoutines, $planDate);
    }

    private function seedInitialRoutines(int $userId): void
    {
        $defaults = [
            // Rutina Matutina
            ['title' => 'Levantarme a tiempo y aseo personal', 'time_block' => 'morning', 'category' => 'salud', 'scheduled_time' => '07:00', 'estimated_minutes' => 20, 'sort_order' => 1],
            ['title' => 'Desayuno nutritivo e hidratación (vaso de agua)', 'time_block' => 'morning', 'category' => 'salud', 'scheduled_time' => '07:30', 'estimated_minutes' => 25, 'sort_order' => 2],
            ['title' => 'Revisar agenda y elegir 3 misiones clave del día', 'time_block' => 'morning', 'category' => 'estudio', 'scheduled_time' => '08:00', 'estimated_minutes' => 10, 'sort_order' => 3],
            // Bloque de Foco / Tarde
            ['title' => 'Bloque de estudio / Pomodoro matutino', 'time_block' => 'afternoon', 'category' => 'estudio', 'scheduled_time' => '10:00', 'estimated_minutes' => 50, 'sort_order' => 4],
            ['title' => 'Almuerzo y descanso sin pantallas', 'time_block' => 'afternoon', 'category' => 'salud', 'scheduled_time' => '13:00', 'estimated_minutes' => 45, 'sort_order' => 5],
            ['title' => 'Sesión de misiones / avance de cursos', 'time_block' => 'afternoon', 'category' => 'estudio', 'scheduled_time' => '15:30', 'estimated_minutes' => 60, 'sort_order' => 6],
            // Rutina Nocturna
            ['title' => 'Cena ligera y actividad física / estiramientos', 'time_block' => 'night', 'category' => 'salud', 'scheduled_time' => '19:30', 'estimated_minutes' => 30, 'sort_order' => 7],
            ['title' => 'Daily Shutdown: balance del día en Diario de Bienestar', 'time_block' => 'night', 'category' => 'personal', 'scheduled_time' => '21:30', 'estimated_minutes' => 15, 'sort_order' => 8],
            ['title' => 'Higiene de sueño: apagar pantallas y lectura', 'time_block' => 'night', 'category' => 'salud', 'scheduled_time' => '22:30', 'estimated_minutes' => 20, 'sort_order' => 9],
        ];

        foreach ($defaults as $data) {
            $this->repository->createRoutine(array_merge($data, [
                'user_id' => $userId,
                'days_of_week' => [1, 2, 3, 4, 5, 6, 7],
                'is_active' => true,
            ]));
        }
    }

    /**
     * @param \Illuminate\Database\Eloquent\Collection<int, DailyPlanItemModel> $items
     * @param \Illuminate\Database\Eloquent\Collection<int, DailyRoutineModel> $routines
     * @return array<string, mixed>
     */
    private function formatPlanResponse($items, $routines, string $planDate): array
    {
        $morning = [];
        $afternoon = [];
        $night = [];
        $anytime = [];

        $total = $items->count();
        $doneCount = 0;
        $skippedCount = 0;
        $postponedCount = 0;
        $pendingCount = 0;
        $nextAction = null;

        foreach ($items as $item) {
            $formatted = [
                'id' => $item->id,
                'routine_id' => $item->routine_id,
                'title' => $item->title,
                'category' => $item->category,
                'time_block' => $item->time_block,
                'scheduled_time' => $item->scheduled_time,
                'estimated_minutes' => $item->estimated_minutes,
                'status' => $item->status,
                'skip_reason' => $item->skip_reason,
                'postponed_to_block' => $item->postponed_to_block,
                'postponed_count' => $item->postponed_count,
                'xp_awarded' => $item->xp_awarded,
                'coins_awarded' => $item->coins_awarded,
                'sort_order' => $item->sort_order,
                'notes' => $item->notes,
                'completed_at' => $item->completed_at?->toIso8601String(),
            ];

            if ($item->status === 'done') {
                $doneCount++;
            } elseif ($item->status === 'skipped') {
                $skippedCount++;
            } elseif ($item->status === 'postponed') {
                $postponedCount++;
            } else {
                $pendingCount++;
                if ($nextAction === null) {
                    $nextAction = $formatted;
                }
            }

            match ($item->time_block) {
                'morning' => $morning[] = $formatted,
                'afternoon' => $afternoon[] = $formatted,
                'night' => $night[] = $formatted,
                default => $anytime[] = $formatted,
            };
        }

        $completionRate = $total > 0 ? (int) round(($doneCount / $total) * 100) : 0;

        return [
            'plan_date' => $planDate,
            'blocks' => [
                'morning' => $morning,
                'afternoon' => $afternoon,
                'night' => $night,
                'anytime' => $anytime,
            ],
            'stats' => [
                'total' => $total,
                'done' => $doneCount,
                'skipped' => $skippedCount,
                'postponed' => $postponedCount,
                'pending' => $pendingCount,
                'completion_rate' => $completionRate,
            ],
            'next_action' => $nextAction,
            'routines' => $routines->map(fn ($r) => [
                'id' => $r->id,
                'title' => $r->title,
                'time_block' => $r->time_block,
                'category' => $r->category,
                'icon' => $r->icon,
                'estimated_minutes' => $r->estimated_minutes,
                'scheduled_time' => $r->scheduled_time,
                'sort_order' => $r->sort_order,
                'days_of_week' => $r->days_of_week,
                'is_active' => $r->is_active,
            ])->toArray(),
        ];
    }
}
