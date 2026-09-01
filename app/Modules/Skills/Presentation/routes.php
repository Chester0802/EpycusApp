<?php

declare(strict_types=1);

use App\Modules\Skills\Presentation\Controllers\SkillsController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/skills', [SkillsController::class, 'index'])->name('skills.index');
    Route::post('/skills', [SkillsController::class, 'store'])->name('skills.store');
    Route::put('/skills/{id}', [SkillsController::class, 'update'])->name('skills.update');
    Route::post('/api/skills/{id}/practice', [SkillsController::class, 'logPractice'])->name('skills.practice');
    Route::delete('/skills/{id}', [SkillsController::class, 'destroy'])->name('skills.destroy');
});
