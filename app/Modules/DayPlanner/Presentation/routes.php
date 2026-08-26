<?php

declare(strict_types=1);

use App\Modules\DayPlanner\Presentation\Controllers\DayPlannerController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/day-planner', [DayPlannerController::class, 'index'])->name('day-planner.index');
    Route::post('/day-planner/items', [DayPlannerController::class, 'storeItem'])->name('day-planner.items.store');
    Route::patch('/day-planner/items/{id}/status', [DayPlannerController::class, 'updateItemStatus'])->name('day-planner.items.status');
    Route::put('/day-planner/items/{id}', [DayPlannerController::class, 'updateItemDetails'])->name('day-planner.items.update');
    Route::delete('/day-planner/items/{id}', [DayPlannerController::class, 'destroyItem'])->name('day-planner.items.destroy');

    Route::post('/day-planner/routines', [DayPlannerController::class, 'storeRoutine'])->name('day-planner.routines.store');
    Route::put('/day-planner/routines/{id}', [DayPlannerController::class, 'updateRoutine'])->name('day-planner.routines.update');
    Route::delete('/day-planner/routines/{id}', [DayPlannerController::class, 'destroyRoutine'])->name('day-planner.routines.destroy');
});
