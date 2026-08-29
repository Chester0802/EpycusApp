<?php

declare(strict_types=1);

use App\Modules\Wellbeing\Presentation\Controllers\WellbeingController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/wellbeing', [WellbeingController::class, 'index'])->name('wellbeing.index');
    Route::get('/wellbeing/day', [WellbeingController::class, 'day'])->name('wellbeing.day');
    Route::post('/wellbeing', [WellbeingController::class, 'store'])->name('wellbeing.store');
    Route::patch('/wellbeing/{id}', [WellbeingController::class, 'update'])->name('wellbeing.update');
    Route::get('/wellbeing/trend', [WellbeingController::class, 'trend'])->name('wellbeing.trend');
});
