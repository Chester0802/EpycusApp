<?php

declare(strict_types=1);

use App\Modules\Learning\Presentation\Controllers\LearningController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/aprendizaje', [LearningController::class, 'index'])->name('learning.index');
    Route::post('/api/learning/chunk/mastery', [LearningController::class, 'updateChunkMastery'])->name('learning.chunk.mastery');
    Route::post('/api/learning/generate-mission', [LearningController::class, 'generateMission'])->name('learning.generate-mission');
});
