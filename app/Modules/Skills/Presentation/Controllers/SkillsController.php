<?php

declare(strict_types=1);

namespace App\Modules\Skills\Presentation\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Gamification\Application\UseCases\AwardXpUseCase;
use App\Modules\Skills\Infrastructure\Models\SkillLogModel;
use App\Modules\Skills\Infrastructure\Models\SkillModel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

final class SkillsController extends Controller
{
    public function __construct(
        private readonly AwardXpUseCase $awardXp,
    ) {}

    public function index(): Response
    {
        $userId = (int) Auth::id();

        $skills = SkillModel::forUser($userId)
            ->withCount(['logs'])
            ->orderByDesc('current_level')
            ->orderByDesc('current_xp')
            ->get()
            ->map(function ($s) {
                $progressPercent = $s->target_xp > 0
                    ? min(100, (int) round(($s->current_xp / $s->target_xp) * 100))
                    : 0;

                return [
                    'id' => $s->id,
                    'name' => $s->name,
                    'category' => $s->category,
                    'icon' => $s->icon ?? 'sparkles',
                    'description' => $s->description,
                    'current_level' => $s->current_level,
                    'current_xp' => $s->current_xp,
                    'target_xp' => $s->target_xp,
                    'progress_percent' => $progressPercent,
                    'total_minutes_practiced' => $s->total_minutes_practiced,
                    'total_hours_practiced' => round($s->total_minutes_practiced / 60, 1),
                    'logs_count' => $s->logs_count,
                    'is_active' => $s->is_active,
                ];
            });

        $recentLogs = SkillLogModel::where('user_id', $userId)
            ->with(['skill'])
            ->orderByDesc('created_at')
            ->limit(10)
            ->get()
            ->map(fn ($log) => [
                'id' => $log->id,
                'skill_name' => $log->skill?->name ?? 'Habilidad',
                'skill_icon' => $log->skill?->icon ?? 'sparkles',
                'duration_minutes' => $log->duration_minutes,
                'xp_earned' => $log->xp_earned,
                'notes' => $log->notes,
                'logged_at' => $log->logged_at->toDateString(),
            ]);

        $totalMinutes = $skills->sum('total_minutes_practiced');
        $masteredCount = $skills->filter(fn ($s) => $s['current_level'] >= 5)->count();

        return Inertia::render('Skills/Index', [
            'skills' => $skills,
            'recentLogs' => $recentLogs,
            'stats' => [
                'total_skills' => $skills->count(),
                'total_hours' => round($totalMinutes / 60, 1),
                'mastered_skills' => $masteredCount,
                'highest_level' => $skills->max('current_level') ?? 1,
            ],
            'xp_awarded' => session()->pull('xp_awarded', 0),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $userId = (int) Auth::id();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'category' => ['required', 'in:technical,soft,language,creative,physical,other'],
            'icon' => ['nullable', 'string', 'max:40'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        SkillModel::create([
            'user_id' => $userId,
            'name' => $validated['name'],
            'category' => $validated['category'],
            'icon' => $validated['icon'] ?? 'sparkles',
            'description' => $validated['description'] ?? null,
            'current_level' => 1,
            'current_xp' => 0,
            'target_xp' => 100,
            'total_minutes_practiced' => 0,
            'is_active' => true,
        ]);

        return back()->with('success', 'Habilidad añadida a tu árbol de destrezas.');
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $userId = (int) Auth::id();
        $skill = SkillModel::where('id', $id)->where('user_id', $userId)->firstOrFail();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'category' => ['required', 'in:technical,soft,language,creative,physical,other'],
            'icon' => ['nullable', 'string', 'max:40'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        $skill->update($validated);

        return back()->with('success', 'Habilidad actualizada.');
    }

    public function logPractice(Request $request, int $id): JsonResponse
    {
        $userId = (int) Auth::id();
        $skill = SkillModel::where('id', $id)->where('user_id', $userId)->firstOrFail();

        $validated = $request->validate([
            'duration_minutes' => ['required', 'integer', 'min:1', 'max:720'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $oldLevel = $skill->current_level;
        $log = $skill->addPractice($validated['duration_minutes'], $validated['notes'] ?? null);
        $leveledUp = $skill->current_level > $oldLevel;

        // Otorgar XP al personaje de gamificación
        $this->awardXp->execute(
            userId: $userId,
            sourceType: 'skill_practice',
            sourceId: $skill->id,
            baseXp: $log->xp_earned,
            dailyCap: 200,
            countsTowardStreak: true
        );

        return response()->json([
            'message' => $leveledUp
                ? "¡Felicidades! Has subido {$skill->name} al Nivel {$skill->current_level} 🎉 (+{$log->xp_earned} XP)"
                : "Práctica registrada con éxito (+{$log->xp_earned} XP)",
            'skill' => $skill,
            'log' => $log,
            'leveled_up' => $leveledUp,
            'xp_awarded' => $log->xp_earned,
        ]);
    }

    public function destroy(int $id): RedirectResponse
    {
        $userId = (int) Auth::id();
        $skill = SkillModel::where('id', $id)->where('user_id', $userId)->firstOrFail();
        $skill->delete();

        return back()->with('success', 'Habilidad eliminada.');
    }
}
