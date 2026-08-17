<?php

declare(strict_types=1);

namespace App\Modules\Villains\Infrastructure\Providers;

use App\Modules\Habits\Domain\Events\HabitCompleted;
use App\Modules\Missions\Domain\Events\MissionCompleted;
use App\Modules\Pomodoro\Domain\Events\PomodoroCompleted;
use App\Modules\StudyGroups\Domain\Events\GroupMessageSent;
use App\Modules\StudyGroups\Domain\Events\ParticipantJoined;
use App\Modules\StudyGroups\Domain\Events\StudySessionCreated;
use App\Modules\Villains\Application\Listeners\HandleHabitCompleted;
use App\Modules\Villains\Application\Listeners\HandleJournalEntryCreated;
use App\Modules\Villains\Application\Listeners\HandleMissionCompleted;
use App\Modules\Villains\Application\Listeners\HandlePomodoroCompleted;
use App\Modules\Villains\Application\Listeners\HandleStudyGroupActivity;
use App\Modules\Villains\Application\UseCases\ApplyDamageUseCase;
use App\Modules\Villains\Application\UseCases\AssignWeeklyVillainUseCase;
use App\Modules\Villains\Application\UseCases\ExpireVillainUseCase;
use App\Modules\Villains\Application\UseCases\GetCurrentVillainUseCase;
use App\Modules\Villains\Domain\Contracts\VillainRepositoryInterface;
use App\Modules\Villains\Infrastructure\Repositories\EloquentVillainRepository;
use App\Modules\Wellbeing\Domain\Events\JournalEntryCreated;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

final class VillainsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(VillainRepositoryInterface::class, EloquentVillainRepository::class);

        $this->app->tag([
            AssignWeeklyVillainUseCase::class,
            ApplyDamageUseCase::class,
            ExpireVillainUseCase::class,
            GetCurrentVillainUseCase::class,
        ], 'villains_use_cases');
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Migrations');
        $this->loadRoutesFrom(__DIR__.'/../../Presentation/routes.php');

        Event::listen(HabitCompleted::class, HandleHabitCompleted::class);
        Event::listen(PomodoroCompleted::class, HandlePomodoroCompleted::class);
        Event::listen(MissionCompleted::class, HandleMissionCompleted::class);
        Event::listen(ParticipantJoined::class, HandleStudyGroupActivity::class);
        Event::listen(GroupMessageSent::class, HandleStudyGroupActivity::class);
        Event::listen(StudySessionCreated::class, HandleStudyGroupActivity::class);
        Event::listen(JournalEntryCreated::class, HandleJournalEntryCreated::class);
    }
}
