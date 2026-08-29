<?php

declare(strict_types=1);

namespace App\Modules\Wellbeing\Infrastructure\Providers;

use App\Modules\Wellbeing\Application\UseCases\CreateEntryUseCase;
use App\Modules\Wellbeing\Application\UseCases\EditEntryUseCase;
use App\Modules\Wellbeing\Application\UseCases\GetDayDetailUseCase;
use App\Modules\Wellbeing\Application\UseCases\GetMonthCalendarUseCase;
use App\Modules\Wellbeing\Application\UseCases\GetMoodTrendUseCase;
use App\Modules\Wellbeing\Domain\Contracts\WellbeingRepositoryInterface;
use App\Modules\Wellbeing\Infrastructure\Repositories\EloquentWellbeingRepository;
use Illuminate\Support\ServiceProvider;

final class WellbeingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(WellbeingRepositoryInterface::class, EloquentWellbeingRepository::class);

        $this->app->tag([
            CreateEntryUseCase::class,
            EditEntryUseCase::class,
            GetDayDetailUseCase::class,
            GetMonthCalendarUseCase::class,
            GetMoodTrendUseCase::class,
        ], 'wellbeing_use_cases');
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Migrations');
        $this->loadRoutesFrom(__DIR__.'/../../Presentation/routes.php');
    }
}
