<?php

declare(strict_types=1);

namespace App\Modules\Telemetry\Infrastructure\Providers;

use App\Modules\Achievements\Domain\Events\AchievementUnlocked;
use App\Modules\Gamification\Domain\Events\LevelUp;
use App\Modules\Gamification\Domain\Events\PhaseUnlocked;
use App\Modules\Gamification\Domain\Events\StreakBroken;
use App\Modules\Gamification\Domain\Events\StreakExtended;
use App\Modules\Gamification\Domain\Events\StreakGraceUsed;
use App\Modules\Gamification\Domain\Events\XpAwarded;
use App\Modules\Habits\Domain\Events\HabitCompleted;
use App\Modules\Habits\Domain\Events\HabitUncompleted;
use App\Modules\Missions\Domain\Events\MissionCompleted;
use App\Modules\Missions\Domain\Events\SubtaskCompleted;
use App\Modules\Pomodoro\Domain\Events\PomodoroAbandoned;
use App\Modules\Pomodoro\Domain\Events\PomodoroCompleted;
use App\Modules\Telemetry\Application\Listeners\DomainEventTelemetryListener;
use App\Modules\Telemetry\Domain\Contracts\TelemetryRepositoryInterface;
use App\Modules\Telemetry\Infrastructure\Repositories\EloquentTelemetryRepository;
use App\Modules\Villains\Domain\Events\VillainAssigned;
use App\Modules\Villains\Domain\Events\VillainDefeated;
use App\Modules\Villains\Domain\Events\VillainSurvived;
use App\Modules\Villains\Domain\Events\VillainWeakened;
use App\Modules\Wellbeing\Domain\Events\JournalEntryCreated;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

final class TelemetryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(TelemetryRepositoryInterface::class, EloquentTelemetryRepository::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Migrations');
        $this->loadRoutesFrom(__DIR__.'/../../Presentation/routes.php');

        // Suscribir el listener genérico de telemetría a los eventos de dominio del backend
        $domainEvents = [
            HabitCompleted::class,
            HabitUncompleted::class,
            PomodoroCompleted::class,
            PomodoroAbandoned::class,
            MissionCompleted::class,
            SubtaskCompleted::class,
            XpAwarded::class,
            LevelUp::class,
            PhaseUnlocked::class,
            StreakExtended::class,
            StreakBroken::class,
            StreakGraceUsed::class,
            AchievementUnlocked::class,
            VillainAssigned::class,
            VillainWeakened::class,
            VillainDefeated::class,
            VillainSurvived::class,
            JournalEntryCreated::class,
        ];

        foreach ($domainEvents as $eventClass) {
            if (class_exists($eventClass)) {
                Event::listen($eventClass, DomainEventTelemetryListener::class);
            }
        }
    }
}
