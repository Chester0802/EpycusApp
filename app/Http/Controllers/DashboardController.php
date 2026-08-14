<?php

namespace App\Http\Controllers;

use App\Modules\Gamification\Domain\Services\LevelCalculator;
use App\Modules\Motivation\Application\UseCases\GetQuoteForLoginUseCase;
use App\Modules\Villains\Application\UseCases\GetCurrentVillainUseCase;
use App\Shared\Domain\Contracts\UserProgressReaderInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(
        private UserProgressReaderInterface $progress,
        private GetCurrentVillainUseCase $getCurrentVillain,
        private GetQuoteForLoginUseCase $getQuote,
    ) {}

    public function index(Request $request): Response|RedirectResponse
    {
        $userId = (int) Auth::id();
        $user = Auth::user();

        // Si el usuario no ha completado su perfil inicial (carrera o institución), redirigir a /profile/complete
        if ($user && (empty($user->career) || empty($user->institution_type))) {
            return redirect()->route('profile.complete');
        }

        // 1. Gamificación y cálculo de progreso de nivel
        $level = $this->progress->getLevelFor($userId);
        $phase = $this->progress->getPhaseFor($userId);
        $totalXp = $this->progress->getTotalXpFor($userId);
        $streak = $this->progress->getCurrentStreakFor($userId);
        $coins = $this->progress->getCoinsFor($userId);

        $levelCalc = app(LevelCalculator::class);
        $accumulated = 0;
        for ($l = 1; $l < $level; $l++) {
            $accumulated += $levelCalc->xpNeededToAdvanceFromLevel($l);
        }
        $currentLevelXp = max(0, $totalXp - $accumulated);
        $nextLevelXpNeeded = $levelCalc->xpNeededToAdvanceFromLevel($level);
        $levelProgressPercent = $nextLevelXpNeeded > 0
            ? min(100, (int) round(($currentLevelXp / $nextLevelXpNeeded) * 100))
            : 100;

        // 2. Actividad de los últimos 7 días
        $tz = new \DateTimeZone('America/Lima');
        $today = new \DateTimeImmutable('now', $tz);
        $last7Days = [];
        $dayNamesEs = ['Do', 'Lu', 'Ma', 'Mi', 'Ju', 'Vi', 'Sá'];

        for ($i = 6; $i >= 0; $i--) {
            $d = $today->modify("-{$i} days");
            $dateStr = $d->format('Y-m-d');
            $dayOfWeek = (int) $d->format('w');
            $last7Days[$dateStr] = [
                'date' => $dateStr,
                'label' => $dayNamesEs[$dayOfWeek],
                'focusMinutes' => 0,
                'habitsDone' => 0,
            ];
        }

        $startDateStr = key($last7Days);
        $endDateStr = $today->format('Y-m-d');

        // Minutos de Pomodoro por día
        $pomodoroDaily = DB::table('pomodoro_sessions')
            ->selectRaw('DATE(started_at) as date, SUM(COALESCE(focus_minutes, planned_minutes)) as total_minutes')
            ->where('user_id', $userId)
            ->where('status', 'completed')
            ->whereBetween(DB::raw('DATE(started_at)'), [$startDateStr, $endDateStr])
            ->groupBy(DB::raw('DATE(started_at)'))
            ->get();

        foreach ($pomodoroDaily as $row) {
            if (isset($last7Days[$row->date])) {
                $last7Days[$row->date]['focusMinutes'] = (int) $row->total_minutes;
            }
        }

        // Hábitos completados por día
        $habitsDaily = DB::table('habit_completions')
            ->selectRaw('completed_for as date, COUNT(*) as total')
            ->where('user_id', $userId)
            ->whereBetween('completed_for', [$startDateStr, $endDateStr])
            ->groupBy('completed_for')
            ->get();

        foreach ($habitsDaily as $row) {
            if (isset($last7Days[$row->date])) {
                $last7Days[$row->date]['habitsDone'] = (int) $row->total;
            }
        }

        // 3. Misiones y desgloses
        $completedMissionsCount = DB::table('missions')
            ->where('user_id', $userId)
            ->whereNotNull('completed_at')
            ->whereNull('deleted_at')
            ->count();

        $overdueMissionsCount = DB::table('missions')
            ->where('user_id', $userId)
            ->whereNull('completed_at')
            ->whereNull('deleted_at')
            ->where('due_date', '<', $today->format('Y-m-d H:i:s'))
            ->count();

        $pendingMissionsCount = DB::table('missions')
            ->where('user_id', $userId)
            ->whereNull('completed_at')
            ->whereNull('deleted_at')
            ->where(function ($q) use ($today) {
                $q->whereNull('due_date')
                  ->orWhere('due_date', '>=', $today->format('Y-m-d H:i:s'));
            })
            ->count();

        $totalMissionsCount = $completedMissionsCount + $overdueMissionsCount + $pendingMissionsCount;

        // 4. Adherencia de Hábitos
        $totalActiveHabits = DB::table('habits')
            ->where('user_id', $userId)
            ->where('is_active', 1)
            ->whereNull('deleted_at')
            ->count();

        $todayHabitsDone = $last7Days[$endDateStr]['habitsDone'] ?? 0;
        $habitAdherencePercent = $totalActiveHabits > 0
            ? min(100, (int) round(($todayHabitsDone / $totalActiveHabits) * 100))
            : ($todayHabitsDone > 0 ? 100 : 0);

        // 5. Resumen de Bienestar Semanal (Últimos 7 días)
        $wellbeingSummary = DB::table('journal_entries')
            ->selectRaw('AVG(mood_score) as avg_mood, AVG(energy) as avg_energy, AVG(stress) as avg_stress, COUNT(*) as total_entries')
            ->where('user_id', $userId)
            ->whereBetween('date', [$startDateStr, $endDateStr])
            ->first();

        // 6. Villano activo
        $villain = $this->getCurrentVillain->execute($userId);

        return Inertia::render('Dashboard', [
            'userName' => $user?->name ?? 'Estudiante',
            'userCareer' => $user?->career,
            'avatarStyle' => $user?->avatar_style,
            'avatarGender' => $user?->avatar_gender,
            'avatarOptions' => $user?->avatar_options,
            'progress' => [
                'level' => $level,
                'phase' => $phase,
                'totalXp' => $totalXp,
                'currentLevelXp' => $currentLevelXp,
                'nextLevelXpNeeded' => $nextLevelXpNeeded,
                'levelProgressPercent' => $levelProgressPercent,
                'currentStreak' => $streak,
                'coins' => $coins,
            ],
            'activity' => array_values($last7Days),
            'stats' => [
                'pendingMissions' => $pendingMissionsCount,
                'completedMissions' => $completedMissionsCount,
                'overdueMissions' => $overdueMissionsCount,
                'totalMissions' => $totalMissionsCount,
                'todayFocusMinutes' => $last7Days[$endDateStr]['focusMinutes'] ?? 0,
                'todayHabitsDone' => $todayHabitsDone,
                'totalActiveHabits' => $totalActiveHabits,
                'habitAdherencePercent' => $habitAdherencePercent,
            ],
            'wellbeing' => [
                'avgMood' => $wellbeingSummary?->avg_mood ? round((float) $wellbeingSummary->avg_mood, 1) : null,
                'avgEnergy' => $wellbeingSummary?->avg_energy ? round((float) $wellbeingSummary->avg_energy, 1) : null,
                'avgStress' => $wellbeingSummary?->avg_stress ? round((float) $wellbeingSummary->avg_stress, 1) : null,
                'totalEntries' => (int) ($wellbeingSummary?->total_entries ?? 0),
            ],
            'villain' => $villain,
            'motivationalQuote' => $this->getQuote->execute($userId),
        ]);
    }
}
