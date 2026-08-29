<?php

declare(strict_types=1);

namespace App\Modules\DayPlanner\Infrastructure\Providers;

use App\Modules\DayPlanner\Domain\Contracts\DayPlanRepositoryInterface;
use App\Modules\DayPlanner\Infrastructure\Repositories\EloquentDayPlanRepository;
use Illuminate\Support\ServiceProvider;

final class DayPlannerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(DayPlanRepositoryInterface::class, EloquentDayPlanRepository::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Migrations');
        $this->loadRoutesFrom(__DIR__.'/../../Presentation/routes.php');
    }
}
