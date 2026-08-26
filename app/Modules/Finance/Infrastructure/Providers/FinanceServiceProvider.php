<?php

declare(strict_types=1);

namespace App\Modules\Finance\Infrastructure\Providers;

use App\Modules\Finance\Domain\Contracts\FinanceRepositoryInterface;
use App\Modules\Finance\Infrastructure\Repositories\EloquentFinanceRepository;
use Illuminate\Support\ServiceProvider;

final class FinanceServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(FinanceRepositoryInterface::class, EloquentFinanceRepository::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Migrations');
        $this->loadRoutesFrom(__DIR__ . '/../../Presentation/routes.php');
    }
}
