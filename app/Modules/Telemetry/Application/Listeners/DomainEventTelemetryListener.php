<?php

declare(strict_types=1);

namespace App\Modules\Telemetry\Application\Listeners;

use App\Modules\Telemetry\Application\DTOs\RecordTelemetryEventDTO;
use App\Modules\Telemetry\Application\UseCases\RecordEventBatchUseCase;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

final readonly class DomainEventTelemetryListener
{
    public function __construct(private RecordEventBatchUseCase $recordTelemetry) {}

    public function handle(object|string $event, mixed $payloadData = null): void
    {
        try {
            $eventObj = is_object($event) ? $event : (is_array($payloadData) && isset($payloadData[0]) && is_object($payloadData[0]) ? $payloadData[0] : null);

            if (! $eventObj) {
                return;
            }

            $eventClass = get_class($eventObj);
            $mapping = $this->mapEvent($eventClass, $eventObj);

            if ($mapping === null) {
                return;
            }

            [$eventName, $category, $userId, $payload] = $mapping;

            if ($userId <= 0) {
                return;
            }

            $dto = new RecordTelemetryEventDTO(
                userId: $userId,
                eventName: $eventName,
                eventCategory: $category,
                payload: $payload,
                sessionUuid: null,
                occurredAt: Carbon::now('UTC')->toIso8601String(),
                source: 'backend'
            );

            $this->recordTelemetry->execute([$dto]);
        } catch (\Throwable $e) {
            // Regla de Telemetría: NUNCA propagar errores de telemetría para no romper la experiencia
            Log::channel('single')->error('Fallo al registrar telemetría de dominio backend', [
                'event' => get_class($event),
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * @return array{0: string, 1: string, 2: int, 3: array<string, mixed>}|null
     */
    private function mapEvent(string $eventClass, object $event): ?array
    {
        $userId = (int) ($event->userId ?? $event->user_id ?? 0);

        return match (true) {
            str_contains($eventClass, 'HabitCompleted') => [
                'habit.completed', 'habits', $userId, [
                    'habit_id' => $event->habitId ?? null,
                    'completed_for' => $event->completedFor ?? null,
                    'is_late' => $event->isLate ?? false,
                ],
            ],

            str_contains($eventClass, 'HabitUncompleted') => [
                'habit.uncompleted', 'habits', $userId, [
                    'habit_id' => $event->habitId ?? null,
                ],
            ],
            str_contains($eventClass, 'PomodoroCompleted') => [
                'pomodoro.completed', 'pomodoro', $userId, [
                    'session_id' => $event->sessionId ?? null,
                    'focus_minutes' => $event->focusMinutes ?? 0,
                    'mission_id' => $event->missionId ?? null,
                ],
            ],
            str_contains($eventClass, 'PomodoroAbandoned') => [
                'pomodoro.abandoned', 'pomodoro', $userId, [
                    'session_id' => $event->sessionId ?? null,
                    'elapsed_minutes' => $event->elapsedMinutes ?? 0,
                    'reason' => $event->reason ?? 'user_cancelled',
                ],
            ],
            str_contains($eventClass, 'MissionCompleted') => [
                'mission.completed', 'missions', $userId, [
                    'mission_id' => $event->missionId ?? null,
                    'days_early_or_late' => $event->daysEarlyOrLate ?? 0,
                    'subtask_count' => $event->subtaskCount ?? 0,
                ],
            ],
            str_contains($eventClass, 'SubtaskCompleted') => [
                'mission.subtask_completed', 'missions', $userId, [
                    'mission_id' => $event->missionId ?? null,
                    'subtask_id' => $event->subtaskId ?? null,
                    'remaining' => $event->remaining ?? 0,
                ],
            ],
            str_contains($eventClass, 'XpAwarded') => [
                'xp.awarded', 'gamification', $userId, [
                    'amount' => $event->amount ?? 0,
                    'source' => $event->sourceType ?? ($event->source ?? 'unknown'),
                    'was_capped' => $event->wasCapped ?? false,
                    'total_xp' => $event->newTotalXp ?? ($event->totalXp ?? 0),
                ],
            ],

            str_contains($eventClass, 'LevelUp') => [
                'level.up', 'gamification', $userId, [
                    'new_level' => $event->newLevel ?? 1,
                    'new_phase' => $event->newPhase ?? 1,
                    'days_since_start' => $event->daysSinceStart ?? 0,
                ],
            ],
            str_contains($eventClass, 'PhaseUnlocked') => [
                'phase.unlocked', 'gamification', $userId, [
                    'phase' => $event->phase ?? 1,
                    'style' => $event->style ?? 'default',
                ],
            ],
            str_contains($eventClass, 'StreakExtended') => [
                'streak.extended', 'gamification', $userId, [
                    'days' => $event->days ?? 0,
                    'bonus_multiplier' => $event->bonusMultiplier ?? 1.0,
                ],
            ],
            str_contains($eventClass, 'StreakBroken') => [
                'streak.broken', 'gamification', $userId, [
                    'previous_days' => $event->previousDays ?? 0,
                    'grace_used' => $event->graceUsed ?? false,
                ],
            ],
            str_contains($eventClass, 'StreakGraceUsed') => [
                'streak.grace_used', 'gamification', $userId, [
                    'remaining_grace' => $event->remainingGrace ?? 0,
                ],
            ],
            str_contains($eventClass, 'AchievementUnlocked') => [
                'achievement.unlocked', 'achievements', $userId, [
                    'achievement_id' => $event->achievementId ?? null,
                    'category' => $event->category ?? 'general',
                    'xp_reward' => $event->xpReward ?? 0,
                ],
            ],
            str_contains($eventClass, 'VillainAssigned') => [
                'villain.assigned', 'villains', $userId, [
                    'villain_id' => $event->villainId ?? null,
                    'villain_type' => $event->villainType ?? 'weekly',
                    'week_number' => $event->weekNumber ?? 1,
                ],
            ],
            str_contains($eventClass, 'VillainWeakened') => [
                'villain.weakened', 'villains', $userId, [
                    'villain_id' => $event->villainId ?? null,
                    'damage' => $event->damage ?? 0,
                    'remaining_hp' => $event->remainingHp ?? 0,
                ],
            ],
            str_contains($eventClass, 'VillainDefeated') => [
                'villain.defeated', 'villains', $userId, [
                    'villain_id' => $event->villainId ?? null,
                    'days_taken' => $event->daysTaken ?? 1,
                ],
            ],
            str_contains($eventClass, 'VillainSurvived') => [
                'villain.survived', 'villains', $userId, [
                    'villain_id' => $event->villainId ?? null,
                    'remaining_hp_percent' => $event->remainingHpPercent ?? 100,
                ],
            ],
            str_contains($eventClass, 'JournalEntryCreated') => [
                'journal.entry_created', 'wellbeing', $userId, [
                    'mood_score' => $event->moodScore ?? 3,
                    'entry_length' => $event->entryLength ?? 0,
                    'tags' => $event->tags ?? [],
                ],
            ],
            default => null,
        };
    }
}
