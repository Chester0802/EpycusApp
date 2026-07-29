<?php

declare(strict_types=1);

namespace App\Modules\Missions\Infrastructure\Providers;

use App\Modules\Missions\Domain\Contracts\MissionRepositoryInterface;
use App\Modules\Missions\Infrastructure\Repositories\EloquentMissionRepository;
use Illuminate\Support\ServiceProvider;

final class MissionsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(MissionRepositoryInterface::class, EloquentMissionRepository::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Migrations');
        $this->loadRoutesFrom(__DIR__.'/../../Presentation/routes.php');
    }
}
