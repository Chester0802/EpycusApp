<?php

declare(strict_types=1);

namespace App\Modules\Fitness\Infrastructure\Providers;

use App\Modules\Fitness\Domain\Contracts\FitnessRepositoryInterface;
use App\Modules\Fitness\Infrastructure\Repositories\EloquentFitnessRepository;
use Illuminate\Support\ServiceProvider;

final class FitnessServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(FitnessRepositoryInterface::class, EloquentFitnessRepository::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Migrations');
        $this->loadRoutesFrom(__DIR__ . '/../../Presentation/routes.php');
    }
}
