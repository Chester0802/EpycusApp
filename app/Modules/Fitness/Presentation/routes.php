<?php

declare(strict_types=1);

use App\Modules\Fitness\Presentation\Controllers\FitnessController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/fitness', [FitnessController::class, 'index'])->name('fitness.index');
    Route::post('/fitness/workouts', [FitnessController::class, 'storeWorkout'])->name('fitness.workouts.store');
    Route::post('/fitness/hydration', [FitnessController::class, 'updateHydration'])->name('fitness.hydration.update');
});
