<?php

declare(strict_types=1);

namespace Tests\Feature\Telemetry;

use App\Modules\Gamification\Domain\Events\XpAwarded;
use App\Modules\Habits\Domain\Events\HabitCompleted;
use App\Modules\Identity\Infrastructure\Models\UserModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

final class TelemetryListenerTest extends TestCase
{
    use RefreshDatabase;

    public function test_domain_events_are_automatically_recorded_in_telemetry_events(): void
    {
        /** @var UserModel $user */
        $user = UserModel::factory()->create();

        // Disparar evento de hábito completado
        Event::dispatch(new HabitCompleted(
            habitId: 10,
            userId: $user->id,
            completedFor: '2026-08-10',
            isLate: false,
            occurredAt: new \DateTimeImmutable('now')
        ));

        $this->assertDatabaseHas('telemetry_events', [
            'user_id' => $user->id,
            'event_name' => 'habit.completed',
            'event_category' => 'habits',
            'source' => 'backend',
        ]);

        // Disparar evento de XP otorgado
        Event::dispatch(new XpAwarded(
            userId: $user->id,
            amount: 25,
            sourceType: 'pomodoro',
            wasCapped: false,
            newTotalXp: 125
        ));

        $this->assertDatabaseHas('telemetry_events', [
            'user_id' => $user->id,
            'event_name' => 'xp.awarded',
            'event_category' => 'gamification',
            'source' => 'backend',
        ]);
    }
}
