<?php

declare(strict_types=1);

namespace App\Modules\Learning\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;

final class LearningServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../../Presentation/routes.php');
    }
}
