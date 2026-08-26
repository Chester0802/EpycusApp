<?php

declare(strict_types=1);

namespace App\Modules\Fitness\Application\UseCases;

use App\Modules\Fitness\Domain\Contracts\FitnessRepositoryInterface;
use Carbon\Carbon;

final class GetFitnessOverviewUseCase
{
    private const PREBUILT_ROUTINES = [
        [
            'id' => 'anti-sedentarismo',
            'name' => '⚡ Anti-Sedentarismo de Escritorio',
            'duration_minutes' => 10,
            'calories' => 45,
            'difficulty' => 'Fácil',
            'icon' => '🪑',
            'description' => 'Alivia la tensión de cuello, hombros y espalda baja provocada por horas de estudio y laptop.',
            'exercises' => ['Estiramiento Cervical y Trapecio', 'Movilidad y Descompresión de Muñecas', 'Torsión Torácica en Silla'],
        ],
        [
            'id' => 'full-body-express',
            'name' => '🔥 Full Body Express en Casa',
            'duration_minutes' => 15,
            'calories' => 90,
            'difficulty' => 'Intermedio',
            'icon' => '💪',
            'description' => 'Activación muscular completa sin equipamiento: tren superior, core y piernas.',
            'exercises' => ['Sentadillas de Peso Corporal', 'Flexiones de Brazos (Suelo / Pared)', 'Plancha Isométrica Abdominal', 'Puente de Glúteos'],
        ],
        [
            'id' => 'cardio-quema-estres',
            'name' => '🏃 Cardio Quema-Estrés',
            'duration_minutes' => 12,
            'calories' => 80,
            'difficulty' => 'Fácil-Medio',
            'icon' => '⚡',
            'description' => 'Eleva pulsaciones y libera endorfinas rápidamente para resetear la mente entre bloques de estudio.',
            'exercises' => ['Jumping Jacks (Saltos de Tijera)', 'Sentadillas de Peso Corporal', 'Plancha Isométrica Abdominal'],
        ],
    ];

    public function __construct(
        private readonly FitnessRepositoryInterface $repository,
    ) {}

    public function execute(int $userId): array
    {
        $today = Carbon::now('America/Lima')->toDateString();
        $exercises = $this->repository->getAllExercises();
        $workouts = $this->repository->getWorkoutLogsForUser($userId, 15);
        $hydration = $this->repository->getHydrationForDate($userId, $today);

        $startOfWeek = Carbon::now('America/Lima')->startOfWeek();
        $weeklyWorkouts = $workouts->filter(fn ($w) => $w->performed_at >= $startOfWeek);

        $weeklyMinutes = $weeklyWorkouts->sum('duration_minutes');
        $weeklyCalories = $weeklyWorkouts->sum('calories_burned');
        $weeklySessions = $weeklyWorkouts->count();

        return [
            'todayDate' => $today,
            'exercises' => $exercises->map(fn ($e) => [
                'id' => $e->id,
                'name' => $e->name,
                'slug' => $e->slug,
                'category' => $e->category,
                'difficulty' => $e->difficulty,
                'target_muscles' => $e->target_muscles,
                'instructions' => $e->instructions,
                'default_duration_seconds' => $e->default_duration_seconds,
                'icon' => $e->icon,
            ])->values()->toArray(),
            'routines' => self::PREBUILT_ROUTINES,
            'history' => $workouts->map(fn ($w) => [
                'id' => $w->id,
                'routine_name' => $w->routine_name,
                'duration_minutes' => $w->duration_minutes,
                'calories_burned' => $w->calories_burned,
                'notes' => $w->notes,
                'performed_at' => $w->performed_at->format('Y-m-d H:i'),
            ])->values()->toArray(),
            'hydration' => [
                'glasses' => $hydration->glasses_count,
                'target_glasses' => 8,
                'total_ml' => $hydration->glasses_count * 250,
                'target_ml' => 2000,
                'percentage' => min(100, (int) round(($hydration->glasses_count / 8) * 100)),
                'is_completed' => $hydration->glasses_count >= 8,
            ],
            'weekly_stats' => [
                'sessions_count' => $weeklySessions,
                'total_minutes' => $weeklyMinutes,
                'total_calories' => $weeklyCalories,
            ],
        ];
    }
}
