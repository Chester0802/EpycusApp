<?php

declare(strict_types=1);

namespace App\Modules\AiAssistant\Application\Services;

use App\Shared\Domain\Contracts\UserProgressReaderInterface;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

final class AiContextBuilderService
{
    public function __construct(
        private readonly UserProgressReaderInterface $progressReader,
    ) {}

    public function buildContext(int $userId): string
    {
        $level = $this->progressReader->getLevelFor($userId);
        $phase = $this->progressReader->getPhaseFor($userId);
        $streak = $this->progressReader->getCurrentStreakFor($userId);

        $today = Carbon::now()->toDateString();
        $sevenDaysAgo = Carbon::now()->subDays(7)->toDateString();

        // 1. Métricas de Hábitos (últimos 7 días)
        $habitsDoneToday = DB::table('habit_completions')
            ->join('habits', 'habits.id', '=', 'habit_completions.habit_id')
            ->where('habits.user_id', $userId)
            ->where('habit_completions.completed_for', $today)
            ->count();

        // 2. Minutos de Foco Pomodoro (últimos 7 días)
        $focusMinutesToday = (int) DB::table('pomodoro_sessions')
            ->where('user_id', $userId)
            ->where('status', 'completed')
            ->whereDate('started_at', $today)
            ->sum(DB::raw('COALESCE(focus_minutes, planned_minutes)'));

        $focusMinutesWeek = (int) DB::table('pomodoro_sessions')
            ->where('user_id', $userId)
            ->where('status', 'completed')
            ->whereDate('started_at', '>=', $sevenDaysAgo)
            ->sum(DB::raw('COALESCE(focus_minutes, planned_minutes)'));

        // 3. Resumen de Bienestar (promedio de ánimo sin texto privado)
        $moodEntries = DB::table('journal_entries')
            ->where('user_id', $userId)
            ->whereDate('created_at', '>=', $sevenDaysAgo)
            ->get(['mood_score', 'tags']);

        $avgMood = $moodEntries->isNotEmpty()
            ? round($moodEntries->avg('mood_score'), 1)
            : 'Sin registros recientes';

        $allTags = [];
        foreach ($moodEntries as $entry) {
            if (! empty($entry->tags)) {
                $decoded = json_decode($entry->tags, true);
                if (is_array($decoded)) {
                    $allTags = array_merge($allTags, $decoded);
                }
            }
        }
        $topTags = ! empty($allTags)
            ? implode(', ', array_slice(array_unique($allTags), 0, 3))
            : 'Ninguna';

        return sprintf(
            "Contexto de progreso del participante (Anónimo):\n".
            "- Nivel actual: %d (Fase %d)\n".
            "- Racha activa: %d días\n".
            "- Hábitos completados hoy: %d\n".
            "- Minutos de foco acumulados hoy: %d min (Total 7 días: %d min)\n".
            '- Promedio de ánimo últimos 7 días: %s / 5 (Etiquetas frecuentes: %s)',
            $level,
            $phase,
            $streak,
            $habitsDoneToday,
            $focusMinutesToday,
            $focusMinutesWeek,
            $avgMood,
            $topTags
        );
    }
}
