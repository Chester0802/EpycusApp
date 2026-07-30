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
            // Constancia
            [
                'code' => 'first_streak_7',
                'name' => 'Racha de Bronce',
                'description' => 'Manten una racha de 7 días consecutivos cumpliendo tus metas.',
                'category' => 'constancia',
                'icon' => '🔥',
                'xp_reward' => 30,
            ],
            [
                'code' => 'first_streak_14',
                'name' => 'Racha de Plata',
                'description' => 'Manten una racha de 14 días consecutivos de estudio constante.',
                'category' => 'constancia',
                'icon' => '⚡',
                'xp_reward' => 50,
            ],
            [
                'code' => 'first_streak_30',
                'name' => 'Racha de Oro',
                'description' => 'Manten una racha de 30 días consecutivos. ¡Constancia ejemplar!',
                'category' => 'constancia',
                'icon' => '🌟',
                'xp_reward' => 100,
            ],
            // Volumen Pomodoro
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
            // Progresión Avatar
            [
                'code' => 'avatar_phase_3',
                'name' => 'Evolución Visual',
                'description' => 'Alcanza la Fase 3 en el progreso de tu avatar.',
                'category' => 'progresion',
                'icon' => '🥋',
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
            // Villanos
            [
                'code' => 'defeat_villain_1',
                'name' => 'Cazador de Obstáculos',
                'description' => 'Derrota a tu primer Villano Semanal cumpliendo hábitos.',
                'category' => 'villanos',
                'icon' => '⚔️',
                'xp_reward' => 50,
            ],
            [
                'code' => 'defeat_villain_5',
                'name' => 'Imparable',
                'description' => 'Derrota a 5 Villanos Semanales.',
                'category' => 'villanos',
                'icon' => '🛡️',
                'xp_reward' => 100,
            ],
            // Bienestar
            [
                'code' => 'journal_7',
                'name' => 'Reflexión Diario',
                'description' => 'Escribe 7 entradas en tu Diario de Bienestar.',
                'category' => 'bienestar',
                'icon' => '📔',
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
            // Puntualidad
            [
                'code' => 'punctual_5',
                'name' => 'Cumplidor de Misiones',
                'description' => 'Completa 5 misiones antes de la fecha límite.',
                'category' => 'puntualidad',
                'icon' => '🎯',
                'xp_reward' => 40,
            ],
        ];

        foreach ($catalog as $item) {
            AchievementModel::firstOrCreate(
                ['code' => $item['code']],
                $item
            );
        }
    }
}
