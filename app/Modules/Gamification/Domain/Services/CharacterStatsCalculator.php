<?php

declare(strict_types=1);

namespace App\Modules\Gamification\Domain\Services;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

final class CharacterStatsCalculator
{
    /**
     * Calcula los 5 atributos RPG (0 a 100) y el título de clase dinámico para un usuario.
     *
     * @return array{
     *     concentration: int,
     *     discipline: int,
     *     resilience: int,
     *     serenity: int,
     *     attack: int,
     *     classTitle: string,
     *     classDescription: string,
     *     dominantStat: string,
     *     totalPowerScore: int,
     *     attributes: array<string, array{name: string, value: int, icon: string, description: string}>
     * }
     */
    public function calculate(int $userId, int $level, int $currentStreak): array
    {
        $tz = new \DateTimeZone('America/Lima');
        $now = CarbonImmutable::now($tz);
        $thirtyDaysAgo = $now->subDays(30)->format('Y-m-d H:i:s');

        // 1. Concentración (Foco Pomodoro)
        $pomodoroStats = DB::table('pomodoro_sessions')
            ->where('user_id', $userId)
            ->where('status', 'completed')
            ->selectRaw('COUNT(*) as total_sessions, SUM(COALESCE(focus_minutes, planned_minutes)) as total_minutes')
            ->first();

        $totalFocusMinutes = (int) ($pomodoroStats?->total_minutes ?? 0);
        $totalPomodoros = (int) ($pomodoroStats?->total_sessions ?? 0);
        // 500 minutos = ~100 puntos de concentración base
        $concentration = min(100, (int) round(($totalFocusMinutes / 5) + ($totalPomodoros * 2)));

        // 2. Disciplina (Misiones y Subtareas)
        $completedMissions = DB::table('missions')
            ->where('user_id', $userId)
            ->whereNotNull('completed_at')
            ->whereNull('deleted_at')
            ->count();

        $completedSubtasks = DB::table('subtasks')
            ->join('missions', 'missions.id', '=', 'subtasks.mission_id')
            ->where('missions.user_id', $userId)
            ->where('subtasks.is_completed', true)
            ->count();

        // 10 misiones + 20 subtareas = 100 puntos
        $discipline = min(100, (int) round(($completedMissions * 8) + ($completedSubtasks * 2)));

        // 3. Resistencia (Racha & Hábitos)
        $totalHabitsDone = DB::table('habit_completions')
            ->where('user_id', $userId)
            ->count();

        // Racha de 10 días + 30 hábitos completados = 100 puntos
        $resilience = min(100, (int) round(($currentStreak * 8) + ($totalHabitsDone * 2)));

        // 4. Serenidad (Bienestar & Manejo de Estrés)
        $wellbeingStats = DB::table('journal_entries')
            ->where('user_id', $userId)
            ->selectRaw('COUNT(*) as total_entries, AVG(mood_score) as avg_mood, AVG(stress) as avg_stress')
            ->first();

        $totalJournals = (int) ($wellbeingStats?->total_entries ?? 0);
        $avgMood = (float) ($wellbeingStats?->avg_mood ?? 3.0);
        $avgStress = (float) ($wellbeingStats?->avg_stress ?? 2.5);

        $moodFactor = max(0.5, ($avgMood * 1.2) - ($avgStress * 0.8));
        $serenity = min(100, (int) round(($totalJournals * 12) + ($moodFactor * 8)));

        // 5. Poder de Ataque (Villanos y Combate)
        $villainStats = DB::table('villain_instances')
            ->where('user_id', $userId)
            ->selectRaw('COUNT(CASE WHEN status = "defeated" THEN 1 END) as defeated_count, SUM(total_hp - remaining_hp) as damage_dealt')
            ->first();

        $defeatedBosses = (int) ($villainStats?->defeated_count ?? 0);
        $damageDealt = (int) ($villainStats?->damage_dealt ?? 0);

        $attack = min(100, (int) round(($defeatedBosses * 25) + ($damageDealt / 3)));

        // Nivel base mínimo de atributos según nivel del personaje
        $baseFloor = min(20, (int) round($level * 1.5));
        $concentration = max($baseFloor, $concentration);
        $discipline = max($baseFloor, $discipline);
        $resilience = max($baseFloor, $resilience);
        $serenity = max($baseFloor, $serenity);
        $attack = max($baseFloor, $attack);

        $attributesMap = [
            'concentration' => [
                'name' => 'Concentración',
                'value' => $concentration,
                'icon' => 'brain',
                'description' => 'Poder de atención sostenida cultivado en sesiones Pomodoro.',
            ],
            'discipline' => [
                'name' => 'Disciplina',
                'value' => $discipline,
                'icon' => 'target',
                'description' => 'Capacidad de ejecución y culminación de misiones y subtareas.',
            ],
            'resilience' => [
                'name' => 'Resistencia',
                'value' => $resilience,
                'icon' => 'flame',
                'description' => 'Fuerza de constancia diaria y protección de tu racha de hábitos.',
            ],
            'serenity' => [
                'name' => 'Serenidad',
                'value' => $serenity,
                'icon' => 'heart',
                'description' => 'Equilibrio mental, autocuidado y reflexión en tu diario de bienestar.',
            ],
            'attack' => [
                'name' => 'Poder de Ataque',
                'value' => $attack,
                'icon' => 'sword',
                'description' => 'Impacto directo infligido a los villanos y obstáculos académicos.',
            ],
        ];

        // Determinación del arquetipo y título de clase
        $scores = [
            'concentration' => $concentration,
            'discipline' => $discipline,
            'resilience' => $resilience,
            'serenity' => $serenity,
            'attack' => $attack,
        ];

        arsort($scores);
        $dominantKey = array_key_first($scores) ?? 'concentration';
        $dominantValue = $scores[$dominantKey];

        [$classTitle, $classDescription] = $this->resolveClassTitle($dominantKey, $level, $dominantValue);

        $totalPowerScore = (int) round(($concentration + $discipline + $resilience + $serenity + $attack) / 5);

        return [
            'concentration' => $concentration,
            'discipline' => $discipline,
            'resilience' => $resilience,
            'serenity' => $serenity,
            'attack' => $attack,
            'classTitle' => $classTitle,
            'classDescription' => $classDescription,
            'dominantStat' => $dominantKey,
            'totalPowerScore' => $totalPowerScore,
            'attributes' => $attributesMap,
        ];
    }

    /**
     * Resuelve el título de clase dinámico de estilo RPG.
     *
     * @return array{0: string, 1: string}
     */
    private function resolveClassTitle(string $dominantKey, int $level, int $score): array
    {
        $prefix = match (true) {
            $level >= 40 => 'Gran Maestro',
            $level >= 30 => 'Archimago',
            $level >= 20 => 'Comandante',
            $level >= 10 => 'Paladín',
            $level >= 5 => 'Iniciado',
            default => 'Aspirante',
        };

        return match ($dominantKey) {
            'concentration' => [
                "{$prefix} del Enfoque Profundo",
                'Tu mente es un santuario inquebrantable. Conviertes cada bloque de estudio en un flujo de concentración absoluta.',
            ],
            'discipline' => [
                "{$prefix} de la Ejecución Implacable",
                'No dejas tareas a medias. Tu método estructurado convierte retos complejos en metas cumplidas.',
            ],
            'resilience' => [
                "{$prefix} de la Constancia Eterna",
                'Tu fortaleza reside en los hábitos diarios. Día tras día forjas tu victoria con determinación inagotable.',
            ],
            'serenity' => [
                "{$prefix} de la Mente Serena",
                'El estrés no nubla tu juicio. Cuidas tu bienestar emocional para aprender con claridad y lucidez.',
            ],
            'attack' => [
                "{$prefix} Cazador de Sombras",
                'No temes a los villanos académicos. Destruyes la procrastinación y la distracción con golpes certeros.',
            ],
            default => [
                "{$prefix} de Epycus",
                'Un héroe en pleno ascenso hacia la maestría y el autodescubrimiento académico.',
            ],
        };
    }

    /**
     * Retorna la definición completa del Camino del Héroe (10 Fases y 50 Niveles).
     *
     * @return array<int, array{
     *     phase: int,
     *     name: string,
     *     tagline: string,
     *     levelRange: string,
     *     minLevel: int,
     *     maxLevel: int,
     *     icon: string,
     *     color: string,
     *     rewards: array<int, string>,
     *     lore: string
     * }>
     */
    public function getHerosJourneyPhases(): array
    {
        return [
            1 => [
                'phase' => 1,
                'name' => 'El Despertar del Aspirante',
                'tagline' => 'Primeros pasos hacia el autodominio.',
                'levelRange' => 'Niveles 1 – 5',
                'minLevel' => 1,
                'maxLevel' => 5,
                'icon' => 'sparkles',
                'color' => '#10b981', // emerald
                'rewards' => [
                    'Desbloqueo de Misiones y Subtareas',
                    'Rutinas y Hábitos iniciales',
                    'Insignia de Aspirante Académico',
                ],
                'lore' => 'Todo gran viaje comienza con la decisión de ser mejor. Aprendes los fundamentos de la organización y vences tus primeras dudas.',
            ],
            2 => [
                'phase' => 2,
                'name' => 'El Forjador del Hábito',
                'tagline' => 'La constancia empieza a rendir frutos.',
                'levelRange' => 'Niveles 6 – 10',
                'minLevel' => 6,
                'maxLevel' => 10,
                'icon' => 'hammer',
                'color' => '#0284c7', // sky
                'rewards' => [
                    'Multiplicador de Monedas +10%',
                    'Accesorios de estudio en Avatar',
                    'Marco de Bronce para tu perfil',
                ],
                'lore' => 'Las acciones repetidas se convierten en tu escudo. Tu racha diaria comienza a ser respetada por tus compañeros.',
            ],
            3 => [
                'phase' => 3,
                'name' => 'El Estratega del Tiempo',
                'tagline' => 'Priorizar con maestría antes de actuar.',
                'levelRange' => 'Niveles 11 – 15',
                'minLevel' => 11,
                'maxLevel' => 15,
                'icon' => 'clock',
                'color' => '#6366f1', // indigo
                'rewards' => [
                    'Cuadrantes de Eisenhower Interactivos',
                    'Acceso a análisis de foco Pomodoro',
                    'Insignia de Estratega Q2',
                ],
                'lore' => 'Distingues lo urgente de lo verdaderamente importante. Planificas con calma para estudiar sin pánico.',
            ],
            4 => [
                'phase' => 4,
                'name' => 'El Especialista Académico',
                'tagline' => 'Tu vocación se refleja en tu vestimenta.',
                'levelRange' => 'Niveles 16 – 20',
                'minLevel' => 16,
                'maxLevel' => 20,
                'icon' => 'book-open',
                'color' => '#8b5cf6', // purple
                'rewards' => [
                    'Desbloqueo de Traje de Carrera en Avatar',
                    'Marco de Plata para tu Credencial',
                    '+20% daño a villanos afines',
                ],
                'lore' => 'Vistes con orgullo el uniforme de tu profesión. La teoría se conecta con la práctica y tu identidad se fortalece.',
            ],
            5 => [
                'phase' => 5,
                'name' => 'El Domador de la Atención',
                'tagline' => 'Concentración profunda a voluntad.',
                'levelRange' => 'Niveles 21 – 25',
                'minLevel' => 21,
                'maxLevel' => 25,
                'icon' => 'brain',
                'color' => '#d946ef', // fuchsia
                'rewards' => [
                    'Aura Azul de Concentración en Avatar',
                    'Fondos de pantalla exclusivos',
                    'Título honorífico: Paladín del Foco',
                ],
                'lore' => 'Las distracciones rebotan en tu campo de enfoque. Entras en estado de flujo profundo en minutos.',
            ],
            6 => [
                'phase' => 6,
                'name' => 'El Guardián del Foco',
                'tagline' => 'Protector inquebrantable de tus metas.',
                'levelRange' => 'Niveles 26 – 30',
                'minLevel' => 26,
                'maxLevel' => 30,
                'icon' => 'shield',
                'color' => '#f59e0b', // amber
                'rewards' => [
                    'Protección de Racha (Días de Gracia)',
                    'Marco Dorado Brillante',
                    'Recompensa de 100 Monedas',
                ],
                'lore' => 'Aunque el semestre se torne tormentoso, mantienes tus estándares en alto y guías a tu grupo de estudio.',
            ],
            7 => [
                'phase' => 7,
                'name' => 'El Sabio Resiliente',
                'tagline' => 'Equilibrio perfecto entre mente y cuerpo.',
                'levelRange' => 'Niveles 31 – 35',
                'minLevel' => 31,
                'maxLevel' => 35,
                'icon' => 'leaf',
                'color' => '#14b8a6', // teal
                'rewards' => [
                    'Resiliencia Mejorada contra el Burnout',
                    'Efectos visuales de calma en Diario',
                    'Título honorífico: Sabio de Epycus',
                ],
                'lore' => 'Entendiste que descansar bien es parte del rendimiento. Tu equilibrio emocional es tu mayor ventaja competitiva.',
            ],
            8 => [
                'phase' => 8,
                'name' => 'El Cazador Legendario',
                'tagline' => 'Pesadilla de los villanos del semestre.',
                'levelRange' => 'Niveles 36 – 40',
                'minLevel' => 36,
                'maxLevel' => 40,
                'icon' => 'sword',
                'color' => '#ef4444', // red
                'rewards' => [
                    'Aura de Fuego Ardiente en Avatar',
                    'Trofeo de Élite en el Bestiario',
                    'Multiplicador de Monedas +25%',
                ],
                'lore' => 'Los 10 villanos del bestiario tiemblan ante tu llegada. Derrotas las evaluaciones con precisión táctica.',
            ],
            9 => [
                'phase' => 9,
                'name' => 'La Mente Maestra',
                'tagline' => 'Excelencia académica indiscutible.',
                'levelRange' => 'Niveles 41 – 45',
                'minLevel' => 41,
                'maxLevel' => 45,
                'icon' => 'crown',
                'color' => '#eab308', // gold
                'rewards' => [
                    'Marco Platino Holográfico',
                    'Desbloqueo de todos los Wallpapers',
                    'Título honorífico: Archimago de la Sabiduría',
                ],
                'lore' => 'Tu conocimiento y metodología inspiran a toda la comunidad. Dominas el arte de aprender a aprender.',
            ],
            10 => [
                'phase' => 10,
                'name' => 'El Gran Maestro Trascendente',
                'tagline' => 'La cima de la evolución académica.',
                'levelRange' => 'Niveles 46 – 50',
                'minLevel' => 46,
                'maxLevel' => 50,
                'icon' => 'trophy',
                'color' => '#ec4899', // pink/cosmic
                'rewards' => [
                    'Aura Cósmica Estelar Suprema',
                    'Estatua de Honor en el Hall de la Fama',
                    'Insignia de Leyenda Universitaria Trascendente',
                ],
                'lore' => 'Has alcanzado la cima del potencial humano universitario. Tu legado en Epycus perdurará como un estándar de excelencia.',
            ],
        ];
    }
}
