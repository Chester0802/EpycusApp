<?php

declare(strict_types=1);

namespace App\Modules\Admin\Infrastructure\Providers;

use App\Modules\Admin\Presentation\Controllers\AdminController;
use App\Shared\Infrastructure\Http\Middleware\EnsureUserIsAdmin;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

final class AdminServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->registerRoutes();
    }

    private function registerRoutes(): void
    {
        Route::middleware(['web', 'auth', EnsureUserIsAdmin::class])->prefix('admin')->group(function () {
            Route::get('/', [AdminController::class, 'index'])->name('admin.index');
            Route::get('/export/{type}', [AdminController::class, 'exportCsv'])->name('admin.export');
        });
    }
}
