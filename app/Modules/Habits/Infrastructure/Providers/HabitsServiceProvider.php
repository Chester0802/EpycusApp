<?php

declare(strict_types=1);

namespace App\Modules\Habits\Infrastructure\Providers;

use App\Modules\Habits\Domain\Contracts\HabitRepositoryInterface;
use App\Modules\Habits\Infrastructure\Repositories\EloquentHabitRepository;
use Illuminate\Support\ServiceProvider;

final class HabitsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(HabitRepositoryInterface::class, EloquentHabitRepository::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Migrations');
        $this->loadRoutesFrom(__DIR__.'/../../Presentation/routes.php');
    }
}
