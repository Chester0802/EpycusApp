<?php

declare(strict_types=1);

use App\Modules\Readings\Presentation\Controllers\ReadingsController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/readings', [ReadingsController::class, 'index'])->name('readings.index');
    Route::post('/readings', [ReadingsController::class, 'store'])->name('readings.store');
    Route::put('/readings/{id}', [ReadingsController::class, 'update'])->name('readings.update');
    Route::post('/api/readings/{id}/progress', [ReadingsController::class, 'updateProgress'])->name('readings.progress');
    Route::delete('/readings/{id}', [ReadingsController::class, 'destroy'])->name('readings.destroy');
});
