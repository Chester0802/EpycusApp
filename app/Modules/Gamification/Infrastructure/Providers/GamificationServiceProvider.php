<?php

declare(strict_types=1);

namespace App\Modules\Gamification\Infrastructure\Providers;

use App\Modules\Gamification\Application\Listeners\AwardXpFromMissionCompletedListener;
use App\Modules\Gamification\Application\Listeners\AwardXpFromHabitListener;
use App\Modules\Gamification\Application\Listeners\AwardXpFromPomodoroListener;
use App\Modules\Gamification\Application\Listeners\AwardXpFromStudyGroupsListener;
use App\Modules\Gamification\Application\Listeners\AwardXpFromVillainDefeatedListener;
use App\Modules\Gamification\Domain\Contracts\GamificationRepositoryInterface;
use App\Modules\Gamification\Domain\Services\LevelCalculator;
use App\Modules\Gamification\Infrastructure\Repositories\EloquentGamificationRepository;
use App\Modules\Gamification\Infrastructure\Repositories\EloquentUserProgressReader;
use App\Modules\Habits\Domain\Events\HabitCompleted;
use App\Modules\Missions\Domain\Events\MissionCompleted;
use App\Modules\Pomodoro\Domain\Events\PomodoroCompleted;
use App\Modules\StudyGroups\Domain\Events\GroupMessageSent;
use App\Modules\StudyGroups\Domain\Events\ParticipantJoined;
use App\Modules\StudyGroups\Domain\Events\StudySessionCreated;
use App\Modules\Villains\Domain\Events\VillainDefeated;
use App\Shared\Domain\Contracts\UserProgressReaderInterface;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

final class GamificationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(GamificationRepositoryInterface::class, EloquentGamificationRepository::class);
        $this->app->bind(UserProgressReaderInterface::class, EloquentUserProgressReader::class);

        $this->app->singleton(LevelCalculator::class, function () {
            return new LevelCalculator(
                baseXp: (int) config('gamification.level_curve.base'),
                increment: (int) config('gamification.level_curve.increment'),
                maxLevel: (int) config('gamification.level_curve.max_level'),
                levelsPerPhase: (int) config('gamification.phases.levels_per_phase'),
            );
        });
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Migrations');

        // La única "cola de eventos" real de este proyecto: cada módulo se
        // suscribe a lo que le interesa de otro en su propio provider, no
        // hay un EventServiceProvider central (no existe todavía en el
        // repo). Si mañana Achievements o Villains también necesitan
        // reaccionar a HabitCompleted, se agrega OTRO listener acá, no se
        // reemplaza este.
        Event::listen(HabitCompleted::class, AwardXpFromHabitListener::class);
        Event::listen(MissionCompleted::class, AwardXpFromMissionCompletedListener::class);
        Event::listen(PomodoroCompleted::class, AwardXpFromPomodoroListener::class);
        Event::listen(VillainDefeated::class, AwardXpFromVillainDefeatedListener::class);
        Event::listen(ParticipantJoined::class, AwardXpFromStudyGroupsListener::class);
        Event::listen(GroupMessageSent::class, AwardXpFromStudyGroupsListener::class);
        Event::listen(StudySessionCreated::class, AwardXpFromStudyGroupsListener::class);
    }
}
