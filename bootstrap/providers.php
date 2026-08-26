<?php

use App\Modules\Achievements\Infrastructure\Providers\AchievementsServiceProvider;
use App\Modules\Admin\Infrastructure\Providers\AdminServiceProvider;
use App\Modules\AiAssistant\Infrastructure\Providers\AiAssistantServiceProvider;
use App\Modules\Calendar\Infrastructure\Providers\CalendarServiceProvider;
use App\Modules\DayPlanner\Infrastructure\Providers\DayPlannerServiceProvider;
use App\Modules\Finance\Infrastructure\Providers\FinanceServiceProvider;
use App\Modules\Fitness\Infrastructure\Providers\FitnessServiceProvider;
use App\Modules\Gamification\Infrastructure\Providers\GamificationServiceProvider;
use App\Modules\Habits\Infrastructure\Providers\HabitsServiceProvider;
use App\Modules\Identity\Infrastructure\Providers\IdentityServiceProvider;
use App\Modules\Missions\Infrastructure\Providers\MissionsServiceProvider;
use App\Modules\Motivation\Infrastructure\Providers\MotivationServiceProvider;
use App\Modules\Pomodoro\Infrastructure\Providers\PomodoroServiceProvider;
use App\Modules\Ranking\Infrastructure\Providers\RankingServiceProvider;
use App\Modules\Shop\Infrastructure\Providers\ShopServiceProvider;
use App\Modules\StudyGroups\Infrastructure\Providers\StudyGroupsServiceProvider;
use App\Modules\Telemetry\Infrastructure\Providers\TelemetryServiceProvider;
use App\Modules\Villains\Infrastructure\Providers\VillainsServiceProvider;
use App\Modules\Wellbeing\Infrastructure\Providers\WellbeingServiceProvider;
use App\Providers\AppServiceProvider;
use App\Shared\Infrastructure\Providers\SharedServiceProvider;

return [
    AppServiceProvider::class,
    SharedServiceProvider::class,
    IdentityServiceProvider::class,
    TelemetryServiceProvider::class,
    DayPlannerServiceProvider::class,
    ShopServiceProvider::class,
    FinanceServiceProvider::class,
    FitnessServiceProvider::class,
    HabitsServiceProvider::class,
    MissionsServiceProvider::class,
    CalendarServiceProvider::class,
    PomodoroServiceProvider::class,
    WellbeingServiceProvider::class,
    StudyGroupsServiceProvider::class,
    VillainsServiceProvider::class,
    RankingServiceProvider::class,
    AiAssistantServiceProvider::class,
    MotivationServiceProvider::class,
    AdminServiceProvider::class,
    AchievementsServiceProvider::class,
    GamificationServiceProvider::class,
];
