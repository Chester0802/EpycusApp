<?php

declare(strict_types=1);

use App\Modules\Habits\Presentation\Controllers\HabitsController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/habits', [HabitsController::class, 'index'])->name('habits.index');
    Route::post('/habits', [HabitsController::class, 'store'])->name('habits.store');
    Route::post('/habits/{id}/toggle', [HabitsController::class, 'toggle'])->name('habits.toggle');
    Route::patch('/habits/{id}', [HabitsController::class, 'update'])->name('habits.update');
    Route::patch('/habits/{id}/archive', [HabitsController::class, 'archive'])->name('habits.archive');
    Route::patch('/habits/{id}/unarchive', [HabitsController::class, 'unarchive'])->name('habits.unarchive');
    Route::delete('/habits/{id}', [HabitsController::class, 'destroy'])->name('habits.destroy');
});
