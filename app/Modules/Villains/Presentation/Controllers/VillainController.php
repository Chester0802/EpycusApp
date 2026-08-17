<?php

declare(strict_types=1);

namespace App\Modules\Villains\Presentation\Controllers;

use App\Modules\Villains\Application\UseCases\GetCurrentVillainUseCase;
use App\Modules\Villains\Domain\ValueObjects\VillainCode;
use App\Modules\Villains\Infrastructure\Models\VillainInstanceModel;
use App\Modules\Villains\Infrastructure\Models\VillainModel;
use Carbon\Carbon;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Response;

final class VillainController extends Controller
{
    public function __construct(
        private GetCurrentVillainUseCase $getCurrentVillain,
    ) {}

    public function index(): Response
    {
        $userId = (int) Auth::id();
        $currentVillain = $this->getCurrentVillain->execute($userId);

        // 1. Obtener Bestiario Completo con estado de victorias
        $allVillains = VillainModel::all();
        $userInstances = VillainInstanceModel::where('user_id', $userId)->get();

        $bestiary = $allVillains->map(function (VillainModel $v) use ($userInstances, $currentVillain) {
            $code = VillainCode::from($v->code);
            $defeatedInstances = $userInstances->where('villain_id', $v->id)->where('status', 'defeated');
            $timesDefeated = $defeatedInstances->count();
            $lastDefeated = $defeatedInstances->sortByDesc('defeated_at')->first();
            $isCurrent = $currentVillain && $currentVillain['code'] === $v->code;

            return [
                'id' => $v->id,
                'code' => $v->code,
                'name' => $v->name,
                'description' => $v->description,
                'weakness_description' => $v->weakness_description,
                'image_url' => asset($code->imagePath()),
                'times_defeated' => $timesDefeated,
                'is_unlocked' => $timesDefeated > 0 || $isCurrent,
                'is_current' => $isCurrent,
                'last_defeated_at' => $lastDefeated?->defeated_at ? Carbon::parse($lastDefeated->defeated_at)->format('d/m/Y') : null,
            ];
        })->values()->toArray();

        $totalDefeatedCount = $userInstances->where('status', 'defeated')->count();

        // 2. Battle Log: Registros de combate de esta semana
        $battleLog = [];
        if ($currentVillain && isset($currentVillain['assigned_at'])) {
            $assignedAt = $currentVillain['assigned_at'];

            // Pomodoros completados
            $recentPomodoros = DB::table('pomodoro_sessions')
                ->where('user_id', $userId)
                ->where('status', 'completed')
                ->where('created_at', '>=', $assignedAt)
                ->orderByDesc('created_at')
                ->limit(5)
                ->get()
                ->map(fn ($p) => [
                    'source' => 'pomodoro',
                    'action' => "Sesión Pomodoro ({$p->planned_minutes} min)",
                    'damage' => 10,
                    'created_at' => Carbon::parse($p->created_at)->diffForHumans(),
                ]);

            // Misiones completadas
            $recentMissions = DB::table('missions')
                ->where('user_id', $userId)
                ->whereNotNull('completed_at')
                ->where('updated_at', '>=', $assignedAt)
                ->orderByDesc('updated_at')
                ->limit(5)
                ->get()
                ->map(fn ($m) => [
                    'source' => 'mission',
                    'action' => "Misión finalizada: \"{$m->title}\"",
                    'damage' => 10,
                    'created_at' => Carbon::parse($m->updated_at)->diffForHumans(),
                ]);

            // Hábitos completados
            $recentHabits = DB::table('habit_completions')
                ->join('habits', 'habits.id', '=', 'habit_completions.habit_id')
                ->where('habits.user_id', $userId)
                ->where('habit_completions.created_at', '>=', $assignedAt)
                ->orderByDesc('habit_completions.created_at')
                ->limit(5)
                ->get(['habits.title', 'habit_completions.created_at'])
                ->map(fn ($h) => [
                    'source' => 'habit',
                    'action' => "Hábito cumplido: \"{$h->title}\"",
                    'damage' => 10,
                    'created_at' => Carbon::parse($h->created_at)->diffForHumans(),
                ]);

            // Diario de bienestar
            $recentJournals = DB::table('journal_entries')
                ->where('user_id', $userId)
                ->where('created_at', '>=', $assignedAt)
                ->orderByDesc('created_at')
                ->limit(5)
                ->get()
                ->map(fn ($j) => [
                    'source' => 'journal',
                    'action' => 'Entrada en el Diario de Bienestar',
                    'damage' => 10,
                    'created_at' => Carbon::parse($j->created_at)->diffForHumans(),
                ]);

            $battleLog = collect([...$recentPomodoros, ...$recentMissions, ...$recentHabits, ...$recentJournals])
                ->take(8)
                ->values()
                ->toArray();
        }

        return inertia('Villains/Index', [
            'villain' => $currentVillain,
            'bestiary' => $bestiary,
            'battleLog' => $battleLog,
            'stats' => [
                'total_defeated' => $totalDefeatedCount,
                'total_villains' => count($bestiary),
            ],
        ]);
    }
}
