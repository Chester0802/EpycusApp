<?php

declare(strict_types=1);

namespace App\Modules\Readings\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;

final class ReadingsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../../Presentation/routes.php');
    }
}
