<?php

declare(strict_types=1);

namespace App\Modules\Identity\Infrastructure\Providers;

use App\Modules\Identity\Domain\Contracts\ParticipantRepositoryInterface;
use App\Modules\Identity\Domain\Contracts\UserRepositoryInterface;
use App\Modules\Identity\Infrastructure\Repositories\EloquentParticipantRepository;
use App\Modules\Identity\Infrastructure\Repositories\EloquentUserRepository;
use Illuminate\Support\ServiceProvider;

final class IdentityServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(UserRepositoryInterface::class, EloquentUserRepository::class);
        $this->app->bind(ParticipantRepositoryInterface::class, EloquentParticipantRepository::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Migrations');
        $this->loadRoutesFrom(__DIR__.'/../../Presentation/routes.php');
    }
}
