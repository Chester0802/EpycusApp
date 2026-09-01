<?php

namespace Database\Seeders;

use App\Modules\Gamification\Infrastructure\Models\SkillModel;
use Illuminate\Database\Seeder;

class GamificationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $skills = [
            [
                'name' => 'Intelecto',
                'key' => 'intellect',
                'icon' => 'brain',
                'color' => '#8b5cf6', // primary / purple
                'description' => 'Representa tu capacidad académica, lectura y retención de información.',
            ],
            [
                'name' => 'Disciplina',
                'key' => 'discipline',
                'icon' => 'shield',
                'color' => '#3b82f6', // blue
                'description' => 'Tu consistencia, enfoque con Pomodoro y cumplimiento de fechas límite.',
            ],
            [
                'name' => 'Creatividad',
                'key' => 'creativity',
                'icon' => 'palette',
                'color' => '#f59e0b', // amber
                'description' => 'Tu capacidad para resolver problemas y avanzar proyectos de desarrollo.',
            ],
            [
                'name' => 'Vitalidad',
                'key' => 'vitality',
                'icon' => 'zap',
                'color' => '#10b981', // emerald
                'description' => 'Tu energía, salud, descanso y rutinas personales.',
            ],
        ];

        foreach ($skills as $skill) {
            SkillModel::updateOrCreate(
                ['key' => $skill['key']],
                $skill
            );
        }
    }
}
