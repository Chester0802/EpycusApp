<?php

declare(strict_types=1);

namespace App\Modules\Shop\Infrastructure\Providers;

use App\Modules\Shop\Domain\Contracts\ShopRepositoryInterface;
use App\Modules\Shop\Infrastructure\Repositories\EloquentShopRepository;
use Illuminate\Support\ServiceProvider;

final class ShopServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ShopRepositoryInterface::class, EloquentShopRepository::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Migrations');
        $this->loadRoutesFrom(__DIR__ . '/../../Presentation/routes.php');
    }
}
