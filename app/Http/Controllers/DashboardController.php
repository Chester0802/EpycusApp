<?php

namespace App\Http\Controllers;

use App\Modules\Gamification\Domain\Services\LevelCalculator;
use App\Modules\Villains\Application\UseCases\GetCurrentVillainUseCase;
use App\Shared\Domain\Contracts\UserProgressReaderInterface;
use App\Shared\Domain\Services\AvatarAssetResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

use App\Modules\Motivation\Application\UseCases\GetQuoteForLoginUseCase;

class DashboardController extends Controller
{
    public function __construct(
        private UserProgressReaderInterface $progress,
        private AvatarAssetResolver $avatars,
        private GetCurrentVillainUseCase $getCurrentVillain,
        private GetQuoteForLoginUseCase $getQuote,
    ) {}

    public function index(Request $request): Response
    {
        $userId = (int) Auth::id();
        $user = Auth::user();

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

        // 3. Misiones y métricas rápidas
        $pendingMissionsCount = DB::table('missions')
            ->where('user_id', $userId)
            ->whereNull('completed_at')
            ->whereNull('deleted_at')
            ->count();

        $completedMissionsCount = DB::table('missions')
            ->where('user_id', $userId)
            ->whereNotNull('completed_at')
            ->whereNull('deleted_at')
            ->count();

        // 4. Villano activo
        $villain = $this->getCurrentVillain->execute($userId);

        return Inertia::render('Dashboard', [
            'userName' => $user?->name ?? 'Estudiante',
            'userCareer' => $user?->career,
            'avatarStyle' => $user?->avatar_style,
            'avatarGender' => $user?->avatar_gender,
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
                'todayFocusMinutes' => $last7Days[$endDateStr]['focusMinutes'] ?? 0,
                'todayHabitsDone' => $last7Days[$endDateStr]['habitsDone'] ?? 0,
            ],
            'villain' => $villain,
            'motivationalQuote' => $this->getQuote->execute($userId),
            'avatarImage' => $this->avatars->imageForModule($user?->avatar_style, $user?->avatar_gender, 'dashboard'),
        ]);
    }
}
