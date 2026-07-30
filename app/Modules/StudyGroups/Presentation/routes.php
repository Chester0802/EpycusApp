<?php

declare(strict_types=1);

use App\Modules\StudyGroups\Presentation\Controllers\StudyGroupController;
use Illuminate\Support\Facades\Route;

// Las rutas de página y mutación llevan throttle para proteger del abuso.
Route::middleware(['web', 'auth', 'throttle:30,1'])->group(function () {
    Route::get('/study-groups', [StudyGroupController::class, 'index'])->name('study-groups.index');
    Route::get('/study-groups/{id}', [StudyGroupController::class, 'show'])->name('study-groups.show');
    Route::post('/study-groups', [StudyGroupController::class, 'store'])->name('study-groups.store');
    Route::post('/study-groups/{id}/join', [StudyGroupController::class, 'join'])->name('study-groups.join');
    Route::post('/study-groups/{id}/leave', [StudyGroupController::class, 'leave'])->name('study-groups.leave');
});

// Las rutas de API (poll cada 5s, envío de mensajes, pomodoro, sala) necesitan
// un rate más alto para no interferir con la experiencia en tiempo real.
Route::middleware(['web', 'auth', 'throttle:120,1'])->group(function () {
    Route::get('/api/study-sessions/{id}/poll', [StudyGroupController::class, 'poll'])->name('study-groups.poll');
    Route::post('/api/study-sessions/{id}/messages', [StudyGroupController::class, 'messages'])->name('study-groups.messages');
    Route::post('/api/study-sessions/{id}/pomodoro/start', [StudyGroupController::class, 'startPomodoro'])->name('study-groups.pomodoro.start');
    Route::post('/api/study-sessions/{id}/configure', [StudyGroupController::class, 'configure'])->name('study-groups.configure');
    Route::post('/api/study-sessions/{id}/advance', [StudyGroupController::class, 'advance'])->name('study-groups.advance');
});
