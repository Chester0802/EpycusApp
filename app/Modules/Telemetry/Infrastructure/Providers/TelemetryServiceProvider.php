<?php

declare(strict_types=1);

namespace App\Modules\Telemetry\Infrastructure\Providers;

use App\Modules\Telemetry\Domain\Contracts\TelemetryRepositoryInterface;
use App\Modules\Telemetry\Infrastructure\Repositories\EloquentTelemetryRepository;
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
    }
}
