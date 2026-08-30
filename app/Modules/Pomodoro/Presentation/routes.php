<?php

declare(strict_types=1);

use App\Modules\Pomodoro\Presentation\Controllers\PomodoroController;
use App\Modules\Pomodoro\Presentation\Controllers\PomodoroReportController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])->prefix('pomodoro')->name('pomodoro.')->group(function () {
    Route::get('/', [PomodoroController::class, 'index'])->name('index');
    Route::post('/start', [PomodoroController::class, 'start'])->name('start');
    Route::post('/{id}/pause', [PomodoroController::class, 'pause'])->name('pause');
    Route::post('/{id}/resume', [PomodoroController::class, 'resume'])->name('resume');
    Route::post('/{id}/complete', [PomodoroController::class, 'complete'])->name('complete');
    Route::post('/{id}/abandon', [PomodoroController::class, 'abandon'])->name('abandon');
    
    // Focus Report Endpoint
    Route::get('/api/focus-report', [PomodoroReportController::class, 'index'])->name('report.index');
});
