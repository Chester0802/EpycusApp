<?php

declare(strict_types=1);

namespace App\Modules\Motivation\Infrastructure\Providers;

use App\Modules\Motivation\Presentation\Controllers\MotivationController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

final class MotivationServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->registerRoutes();
    }

    private function registerRoutes(): void
    {
        Route::middleware(['web', 'auth'])->group(function () {
            Route::get('/motivation/tip/{module}', [MotivationController::class, 'getTip'])->name('motivation.tip');
            Route::post('/motivation/tip/dismiss', [MotivationController::class, 'dismissTip'])->name('motivation.dismiss-tip');
        });
    }
}
