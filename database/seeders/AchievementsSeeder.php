<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Modules\Achievements\Infrastructure\Models\AchievementModel;
use Illuminate\Database\Seeder;

final class AchievementsSeeder extends Seeder
{
    public function run(): void
    {
        $catalog = [
            // 1. Constancia (Racha)
            [
                'code' => 'first_streak_3',
                'name' => 'Iniciando el Hábito',
                'description' => 'Mantén una racha de 3 días consecutivos estudiando o completando hábitos.',
                'category' => 'constancia',
                'icon' => '🌱',
                'xp_reward' => 15,
            ],
            [
                'code' => 'first_streak_7',
                'name' => 'Racha de Bronce',
                'description' => 'Mantén una racha de 7 días consecutivos cumpliendo tus metas.',
                'category' => 'constancia',
                'icon' => '🔥',
                'xp_reward' => 30,
            ],
            [
                'code' => 'first_streak_14',
                'name' => 'Racha de Plata',
                'description' => 'Mantén una racha de 14 días consecutivos de estudio constante.',
                'category' => 'constancia',
                'icon' => '⚡',
                'xp_reward' => 50,
            ],
            [
                'code' => 'first_streak_30',
                'name' => 'Racha de Oro',
                'description' => 'Mantén una racha de 30 días consecutivos. ¡Constancia ejemplar!',
                'category' => 'constancia',
                'icon' => '🌟',
                'xp_reward' => 100,
            ],

            // 2. Volumen Pomodoro
            [
                'code' => 'pomodoro_1',
                'name' => 'Primer Bloque',
                'description' => 'Completa tu primera sesión de estudio profundo con la técnica Pomodoro.',
                'category' => 'volumen',
                'icon' => '🍅',
                'xp_reward' => 10,
            ],
            [
                'code' => 'pomodoro_10',
                'name' => 'Enfoque Inicial',
                'description' => 'Completa 10 sesiones de estudio Pomodoro.',
                'category' => 'volumen',
                'icon' => '⏱️',
                'xp_reward' => 20,
            ],
            [
                'code' => 'pomodoro_50',
                'name' => 'Maestro del Foco',
                'description' => 'Completa 50 sesiones de estudio Pomodoro.',
                'category' => 'volumen',
                'icon' => '🎯',
                'xp_reward' => 50,
            ],
            [
                'code' => 'pomodoro_100',
                'name' => 'Centinela del Tiempo',
                'description' => 'Completa 100 sesiones de estudio Pomodoro.',
                'category' => 'volumen',
                'icon' => '⏳',
                'xp_reward' => 100,
            ],

            // 3. Misiones & Matriz de Eisenhower
            [
                'code' => 'mission_1',
                'name' => 'Primera Misión',
                'description' => 'Completa tu primera misión académica y divide grandes retos en pasos.',
                'category' => 'misiones',
                'icon' => '🎯',
                'xp_reward' => 15,
            ],
            [
                'code' => 'mission_5',
                'name' => 'Estratega de Misiones',
                'description' => 'Completa 5 misiones académicas con sus subtareas.',
                'category' => 'misiones',
                'icon' => '📋',
                'xp_reward' => 30,
            ],
            [
                'code' => 'mission_20',
                'name' => 'Conquistador de Objetivos',
                'description' => 'Completa 20 misiones académicas.',
                'category' => 'misiones',
                'icon' => '🏆',
                'xp_reward' => 80,
            ],
            [
                'code' => 'eisenhower_q2_5',
                'name' => 'Zona Anti-Procrastinación',
                'description' => 'Completa 5 misiones planificadas en el Cuadrante Q2 (No Urgente pero Importante).',
                'category' => 'misiones',
                'icon' => '🧠',
                'xp_reward' => 40,
            ],
            [
                'code' => 'punctual_5',
                'name' => 'Cero Procrastinación',
                'description' => 'Completa 5 misiones antes o en su fecha límite exacta.',
                'category' => 'misiones',
                'icon' => '⚡',
                'xp_reward' => 40,
            ],

            // 4. Hábitos
            [
                'code' => 'habit_1',
                'name' => 'Semilla del Cambio',
                'description' => 'Marca tu primer hábito diario completado.',
                'category' => 'habitos',
                'icon' => '🌱',
                'xp_reward' => 10,
            ],
            [
                'code' => 'habit_20',
                'name' => 'Rutina Consolidada',
                'description' => 'Alcanza 20 cumplimientos de hábitos diarios.',
                'category' => 'habitos',
                'icon' => '🌿',
                'xp_reward' => 30,
            ],
            [
                'code' => 'habit_50',
                'name' => 'Roble Imparable',
                'description' => 'Alcanza 50 cumplimientos de hábitos diarios.',
                'category' => 'habitos',
                'icon' => '🌳',
                'xp_reward' => 70,
            ],

            // 5. Grupos de Estudio
            [
                'code' => 'study_group_1',
                'name' => 'Estudio Colaborativo',
                'description' => 'Únete o crea una sesión en los Grupos de Estudio Virtuales.',
                'category' => 'estudio_grupal',
                'icon' => '👥',
                'xp_reward' => 20,
            ],

            // 6. Villanos Semanales
            [
                'code' => 'defeat_villain_1',
                'name' => 'Cazador de Obstáculos',
                'description' => 'Derrota a tu primer Villano Semanal cumpliendo hábitos y misiones.',
                'category' => 'villanos',
                'icon' => '⚔️',
                'xp_reward' => 50,
            ],
            [
                'code' => 'defeat_villain_5',
                'name' => 'Defensor del Enfoque',
                'description' => 'Derrota a 5 Villanos Semanales.',
                'category' => 'villanos',
                'icon' => '🛡️',
                'xp_reward' => 100,
            ],

            // 7. Bienestar (Diario)
            [
                'code' => 'journal_1',
                'name' => 'Autoconocimiento',
                'description' => 'Escribe tu primera entrada en el Diario de Bienestar.',
                'category' => 'bienestar',
                'icon' => '📔',
                'xp_reward' => 10,
            ],
            [
                'code' => 'journal_7',
                'name' => 'Reflexión Constante',
                'description' => 'Escribe 7 entradas en tu Diario de Bienestar.',
                'category' => 'bienestar',
                'icon' => '📖',
                'xp_reward' => 30,
            ],
            [
                'code' => 'journal_30',
                'name' => 'Mente Serena',
                'description' => 'Escribe 30 entradas en tu Diario de Bienestar.',
                'category' => 'bienestar',
                'icon' => '🧘',
                'xp_reward' => 80,
            ],

            // 8. Progresión & Avatar
            [
                'code' => 'level_5',
                'name' => 'Estudiante Avanzado',
                'description' => 'Alcanza el Nivel 5 ganando experiencia en la plataforma.',
                'category' => 'progresion',
                'icon' => '🥋',
                'xp_reward' => 30,
            ],
            [
                'code' => 'level_10',
                'name' => 'Erudito Académico',
                'description' => 'Alcanza el Nivel 10.',
                'category' => 'progresion',
                'icon' => '🎓',
                'xp_reward' => 70,
            ],
            [
                'code' => 'avatar_phase_3',
                'name' => 'Evolución Visual',
                'description' => 'Alcanza la Fase 3 en el progreso de tu avatar.',
                'category' => 'progresion',
                'icon' => '✨',
                'xp_reward' => 40,
            ],
            [
                'code' => 'avatar_phase_5',
                'name' => 'Identidad Firme',
                'description' => 'Alcanza la Fase 5 en el progreso de tu avatar.',
                'category' => 'progresion',
                'icon' => '👑',
                'xp_reward' => 70,
            ],
        ];

        foreach ($catalog as $item) {
            AchievementModel::updateOrCreate(
                ['code' => $item['code']],
                $item
            );
        }
    }
}
