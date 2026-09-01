<?php

declare(strict_types=1);

namespace App\Modules\Gamification\Presentation\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Gamification\Infrastructure\Models\SkillModel;
use App\Modules\Gamification\Infrastructure\Models\UserProgressModel;
use App\Modules\Gamification\Infrastructure\Models\UserSkillModel;
use App\Modules\Gamification\Domain\Services\LevelCalculator;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class GamificationController extends Controller
{
    public function __construct(private readonly LevelCalculator $levelCalculator) {}

    public function index(Request $request): Response
    {
        $userId = $request->user()->id;
        
        $progress = UserProgressModel::firstOrCreate(
            ['user_id' => $userId],
            [
                'total_xp' => 0,
                'current_level' => 1,
                'current_phase' => 1,
                'coins' => 0,
            ]
        );

        // Fetch skills and user levels
        $skills = SkillModel::all();
        if ($skills->isEmpty()) {
            $defaultSkills = [
                [
                    'name' => 'Intelecto',
                    'key' => 'intelecto',
                    'icon' => 'brain',
                    'color' => '#3b82f6',
                    'description' => 'Aumenta al estudiar, repasar flashcards y completar misiones académicas.',
                ],
                [
                    'name' => 'Disciplina',
                    'key' => 'disciplina',
                    'icon' => 'shield',
                    'color' => '#10b981',
                    'description' => 'Aumenta al mantener rachas de hábitos y finalizar bloques pomodoro.',
                ],
                [
                    'name' => 'Creatividad',
                    'key' => 'creatividad',
                    'icon' => 'lightbulb',
                    'color' => '#8b5cf6',
                    'description' => 'Aumenta al desarrollar proyectos ABP y registrar apuntes detallados.',
                ],
                [
                    'name' => 'Vitalidad',
                    'key' => 'vitalidad',
                    'icon' => 'heart',
                    'color' => '#f43f5e',
                    'description' => 'Aumenta con pausas activas, hábitos de hidratación y bienestar.',
                ],
            ];

            foreach ($defaultSkills as $ds) {
                SkillModel::firstOrCreate(['key' => $ds['key']], $ds);
            }
            $skills = SkillModel::all();
        }
        $userSkills = UserSkillModel::where('user_id', $userId)->get()->keyBy('skill_id');

        $skillsData = $skills->map(function ($skill) use ($userSkills) {
            $userSkill = $userSkills->get($skill->id);
            $xp = $userSkill ? $userSkill->xp : 0;
            $level = $userSkill ? $userSkill->level : 1;

            // Simple calculation for next level XP (similar to global curve, but simplified)
            $xpForCurrentLevel = pow($level - 1, 2) * 100;
            $xpForNextLevel = pow($level, 2) * 100;
            
            $progressPercent = 0;
            if ($xpForNextLevel > $xpForCurrentLevel) {
                $progressPercent = min(100, max(0, (($xp - $xpForCurrentLevel) / ($xpForNextLevel - $xpForCurrentLevel)) * 100));
            }

            return [
                'id' => $skill->id,
                'name' => $skill->name,
                'key' => $skill->key,
                'icon' => $skill->icon,
                'color' => $skill->color,
                'description' => $skill->description,
                'level' => $level,
                'xp' => $xp,
                'next_level_xp' => $xpForNextLevel,
                'progress_percent' => round($progressPercent),
            ];
        });

        // Global Progress
        $globalXpForNextLevel = $this->levelCalculator->xpForLevel($progress->current_level + 1);
        $globalXpForCurrentLevel = $this->levelCalculator->xpForLevel($progress->current_level);
        $globalProgressPercent = 0;
        if ($globalXpForNextLevel > $globalXpForCurrentLevel) {
            $globalProgressPercent = min(100, max(0, (($progress->total_xp - $globalXpForCurrentLevel) / ($globalXpForNextLevel - $globalXpForCurrentLevel)) * 100));
        }

        return Inertia::render('Gamification/Index', [
            'progress' => [
                'total_xp' => $progress->total_xp,
                'current_level' => $progress->current_level,
                'current_phase' => $progress->current_phase,
                'coins' => $progress->coins,
                'current_streak' => $progress->current_streak,
                'longest_streak' => $progress->longest_streak,
                'next_level_xp' => $globalXpForNextLevel,
                'progress_percent' => round($globalProgressPercent),
            ],
            'skills' => $skillsData,
        ]);
    }
}
