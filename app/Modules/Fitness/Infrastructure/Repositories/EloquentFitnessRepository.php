<?php

declare(strict_types=1);

namespace App\Modules\Fitness\Infrastructure\Repositories;

use App\Modules\Fitness\Domain\Contracts\FitnessRepositoryInterface;
use App\Modules\Fitness\Infrastructure\Models\DailyHydrationLogModel;
use App\Modules\Fitness\Infrastructure\Models\FitnessExerciseModel;
use App\Modules\Fitness\Infrastructure\Models\FitnessWorkoutLogModel;
use App\Modules\Gamification\Application\UseCases\AwardXpUseCase;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class EloquentFitnessRepository implements FitnessRepositoryInterface
{
    private const DEFAULT_EXERCISES = [
        [
            'name' => 'Estiramiento Cervical y Trapecio',
            'slug' => 'estiramiento-cervical',
            'category' => 'escritorio',
            'difficulty' => 'facil',
            'target_muscles' => 'Cuello, Trapecio Superior',
            'instructions' => 'Inclina suavemente la cabeza hacia el hombro derecho con la mano derecha sin forzar. Mantén 20 segundos y cambia de lado. Alivia la tensión por mirar la pantalla.',
            'default_duration_seconds' => 45,
            'icon' => '🧘‍♂️',
        ],
        [
            'name' => 'Movilidad y Descompresión de Muñecas',
            'slug' => 'movilidad-munecas',
            'category' => 'escritorio',
            'difficulty' => 'facil',
            'target_muscles' => 'Antebrazos, Muñecas',
            'instructions' => 'Extiende el brazo al frente con la palma hacia arriba y tira suavemente de los dedos hacia abajo. Rota las muñecas en círculos lentos. Previene el síndrome del túnel carpiano.',
            'default_duration_seconds' => 45,
            'icon' => '🤲',
        ],
        [
            'name' => 'Torsión Torácica en Silla',
            'slug' => 'torsion-toracica-silla',
            'category' => 'escritorio',
            'difficulty' => 'facil',
            'target_muscles' => 'Espalda Media, Columna',
            'instructions' => 'Siéntate erguido, cruza el brazo derecho hacia el respaldo de la silla y rota suavemente el torso hacia la derecha respirando profundo. Cambia de lado.',
            'default_duration_seconds' => 60,
            'icon' => '🪑',
        ],
        [
            'name' => 'Sentadillas de Peso Corporal',
            'slug' => 'sentadillas-peso-corporal',
            'category' => 'fuerza',
            'difficulty' => 'facil',
            'target_muscles' => 'Cuádriceps, Glúteos, Isquiotibiales',
            'instructions' => 'Pies al ancho de los hombros. Desciende empujando las caderas hacia atrás manteniendo el pecho erguido y las rodillas alineadas con los pies. Realiza 12-15 repeticiones.',
            'default_duration_seconds' => 60,
            'icon' => '🦵',
        ],
        [
            'name' => 'Flexiones de Brazos (Suelo / Pared)',
            'slug' => 'flexiones-brazos',
            'category' => 'fuerza',
            'difficulty' => 'medio',
            'target_muscles' => 'Pectorales, Tríceps, Core',
            'instructions' => 'Manos separadas a la anchura de hombros. Cuerpo en línea recta. Desciende hasta que el pecho casi toque el suelo o apóyate en la pared si eres principiante.',
            'default_duration_seconds' => 60,
            'icon' => '💪',
        ],
        [
            'name' => 'Plancha Isométrica Abdominal',
            'slug' => 'plancha-isometrica',
            'category' => 'fuerza',
            'difficulty' => 'medio',
            'target_muscles' => 'Abdomen, Core, Zona Lumbar',
            'instructions' => 'Apoya los antebrazos y puntas de los pies. Mantén el abdomen contraído y la espalda neutral sin dejar caer la cadera. Sostén durante 30 a 45 segundos.',
            'default_duration_seconds' => 45,
            'icon' => '🏋️‍♂️',
        ],
        [
            'name' => 'Jumping Jacks (Saltos de Tijera)',
            'slug' => 'jumping-jacks',
            'category' => 'cardio',
            'difficulty' => 'facil',
            'target_muscles' => 'Cuerpo Completo, Sistema Cardiovascular',
            'instructions' => 'Comienza de pie con los brazos a los lados. Salta abriendo las piernas mientras llevas los brazos sobre la cabeza. Vuelve a la posición inicial en ritmo constante.',
            'default_duration_seconds' => 60,
            'icon' => '🏃‍♂️',
        ],
        [
            'name' => 'Puente de Glúteos',
            'slug' => 'puente-gluteos',
            'category' => 'flexibilidad',
            'difficulty' => 'facil',
            'target_muscles' => 'Glúteos, Isquiotibiales, Lumbar',
            'instructions' => 'Acuéstate boca arriba con las rodillas dobladas y los pies apoyados en el suelo. Eleva la cadera contrayendo los glúteos hasta formar una línea recta de hombros a rodillas.',
            'default_duration_seconds' => 60,
            'icon' => '💥',
        ],
    ];

    public function __construct(
        private readonly AwardXpUseCase $awardXp,
    ) {}

    public function seedDefaultExercisesIfEmpty(): void
    {
        if (FitnessExerciseModel::count() === 0) {
            foreach (self::DEFAULT_EXERCISES as $exercise) {
                FitnessExerciseModel::create($exercise);
            }
        }
    }

    public function getAllExercises(): Collection
    {
        $this->seedDefaultExercisesIfEmpty();

        return FitnessExerciseModel::orderBy('category', 'asc')
            ->orderBy('id', 'asc')
            ->get();
    }

    public function getWorkoutLogsForUser(int $userId, int $limit = 15): Collection
    {
        return FitnessWorkoutLogModel::where('user_id', $userId)
            ->orderBy('performed_at', 'desc')
            ->limit($limit)
            ->get();
    }

    public function logWorkout(int $userId, array $data): array
    {
        return DB::transaction(function () use ($userId, $data) {
            $workout = FitnessWorkoutLogModel::create([
                'user_id' => $userId,
                'routine_name' => $data['routine_name'],
                'duration_minutes' => (int) ($data['duration_minutes'] ?? 15),
                'calories_burned' => (int) ($data['calories_burned'] ?? 70),
                'notes' => $data['notes'] ?? null,
                'performed_at' => $data['performed_at'] ?? Carbon::now(),
            ]);

            // Otorgar XP (+25 XP por sesión de ejercicio)
            $xpResult = $this->awardXp->execute($userId, 'fitness_workout', $workout->id, 25, 4, true);

            return [
                'workout' => $workout,
                'xp_awarded' => $xpResult->xpAwarded,
            ];
        });
    }

    public function getHydrationForDate(int $userId, string $date): DailyHydrationLogModel
    {
        return DailyHydrationLogModel::firstOrCreate(
            ['user_id' => $userId, 'date' => $date],
            ['glasses_count' => 0]
        );
    }

    public function updateHydrationGlasses(int $userId, string $date, int $delta): array
    {
        return DB::transaction(function () use ($userId, $date, $delta) {
            $log = DailyHydrationLogModel::firstOrCreate(
                ['user_id' => $userId, 'date' => $date],
                ['glasses_count' => 0]
            );

            $oldCount = $log->glasses_count;
            $newCount = max(0, min(12, $oldCount + $delta));
            $log->glasses_count = $newCount;
            $log->save();

            $reachedGoal = ($oldCount < 8 && $newCount >= 8);
            $xpAwarded = 0;

            if ($reachedGoal) {
                $xpResult = $this->awardXp->execute($userId, 'hydration_goal', $log->id, 20, 1, false);
                $xpAwarded = $xpResult->xpAwarded;
            }

            return [
                'glasses_count' => $newCount,
                'reached_goal' => $reachedGoal,
                'xp_awarded' => $xpAwarded,
            ];
        });
    }
}
