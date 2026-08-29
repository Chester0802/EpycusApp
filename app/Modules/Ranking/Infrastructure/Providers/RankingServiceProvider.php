<?php

declare(strict_types=1);

namespace App\Modules\Ranking\Infrastructure\Providers;

use App\Modules\Ranking\Presentation\Controllers\RankingController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

final class RankingServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->registerRoutes();
    }

    private function registerRoutes(): void
    {
        Route::middleware(['web', 'auth'])
            ->get('/ranking', [RankingController::class, 'index'])
            ->name('ranking.index');
    }
}
