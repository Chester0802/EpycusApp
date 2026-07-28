<?php

declare(strict_types=1);

use App\Modules\Identity\Presentation\Controllers\ConsentController;
use App\Modules\Identity\Presentation\Controllers\PreferencesController;
use App\Modules\Identity\Presentation\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/profile/complete', [ProfileController::class, 'edit'])->name('profile.complete');
    Route::patch('/profile/complete', [ProfileController::class, 'update']);

    Route::post('/consent', [ConsentController::class, 'store'])->name('consent.store');

    Route::patch('/preferences', [PreferencesController::class, 'update'])->name('preferences.update');
});
