<?php

declare(strict_types=1);

use App\Modules\Missions\Presentation\Controllers\MissionsController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/missions', [MissionsController::class, 'index'])->name('missions.index');
    Route::get('/missions/{id}', [MissionsController::class, 'show'])->name('missions.show');
    Route::post('/missions', [MissionsController::class, 'store'])->name('missions.store');
    Route::patch('/missions/{id}', [MissionsController::class, 'update'])->name('missions.update');
    Route::post('/missions/{id}/complete', [MissionsController::class, 'complete'])->name('missions.complete');
    Route::post('/missions/{id}/uncomplete', [MissionsController::class, 'uncomplete'])->name('missions.uncomplete');
    Route::post('/missions/{id}/quadrant', [MissionsController::class, 'changeQuadrant'])->name('missions.quadrant');
    Route::post('/missions/{id}/subtasks/{subtaskId}/toggle', [MissionsController::class, 'toggleSubtask'])->name('missions.subtasks.toggle');
    Route::patch('/missions/{id}/subtasks/{subtaskId}', [MissionsController::class, 'updateSubtask'])->name('missions.subtasks.update');
    Route::post('/missions/{id}/subtasks', [MissionsController::class, 'addSubtask'])->name('missions.subtasks.store');
    Route::post('/missions/{id}/subtasks/reorder', [MissionsController::class, 'reorderSubtasks'])->name('missions.subtasks.reorder');
    Route::delete('/missions/{id}', [MissionsController::class, 'destroy'])->name('missions.destroy');
});
