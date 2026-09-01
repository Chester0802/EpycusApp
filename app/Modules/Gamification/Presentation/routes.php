<?php

declare(strict_types=1);

use App\Modules\Gamification\Presentation\Controllers\AutomationsController;
use App\Modules\Gamification\Presentation\Controllers\GamificationController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/gamification', [GamificationController::class, 'index'])->name('gamification.index');
    Route::get('/api/automations', [AutomationsController::class, 'index'])->name('automations.index');
    Route::post('/api/automations', [AutomationsController::class, 'store'])->name('automations.store');
    Route::patch('/api/automations/{id}/toggle', [AutomationsController::class, 'toggle'])->name('automations.toggle');
    Route::post('/api/automations/run', [AutomationsController::class, 'runRules'])->name('automations.run');
    Route::delete('/api/automations/{id}', [AutomationsController::class, 'destroy'])->name('automations.destroy');
});
