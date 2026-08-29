<?php

declare(strict_types=1);

namespace App\Modules\Calendar\Infrastructure\Providers;

use App\Modules\Calendar\Domain\Contracts\CalendarRepositoryInterface;
use App\Modules\Calendar\Infrastructure\Repositories\EloquentCalendarRepository;
use App\Shared\Domain\Contracts\CalendarReaderInterface;
use Illuminate\Support\ServiceProvider;

final class CalendarServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(CalendarRepositoryInterface::class, EloquentCalendarRepository::class);
        $this->app->bind(CalendarReaderInterface::class, EloquentCalendarRepository::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Migrations');
        $this->loadRoutesFrom(__DIR__.'/../../Presentation/routes.php');
    }
}
