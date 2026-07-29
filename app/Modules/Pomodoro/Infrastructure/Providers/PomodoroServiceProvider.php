<?php

declare(strict_types=1);

namespace App\Modules\Pomodoro\Infrastructure\Providers;

use App\Modules\Pomodoro\Domain\Contracts\PomodoroRepositoryInterface;
use App\Modules\Pomodoro\Infrastructure\Repositories\EloquentPomodoroRepository;
use Illuminate\Support\ServiceProvider;

final class PomodoroServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(PomodoroRepositoryInterface::class, EloquentPomodoroRepository::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Migrations');
        $this->loadRoutesFrom(__DIR__.'/../../Presentation/routes.php');
    }
}
