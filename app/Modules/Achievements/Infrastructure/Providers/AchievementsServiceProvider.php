<?php

declare(strict_types=1);

namespace App\Modules\Achievements\Infrastructure\Providers;

use App\Modules\Achievements\Presentation\Controllers\AchievementsController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

final class AchievementsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->registerRoutes();
    }

    private function registerRoutes(): void
    {
        Route::middleware(['web', 'auth'])->group(function () {
            Route::get('/achievements', [AchievementsController::class, 'index'])->name('achievements.index');
        });
    }
}
