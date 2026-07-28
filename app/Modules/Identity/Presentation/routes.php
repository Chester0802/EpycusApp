<?php

declare(strict_types=1);

use App\Modules\Identity\Presentation\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/profile/complete', [ProfileController::class, 'edit'])->name('profile.complete');
    Route::patch('/profile/complete', [ProfileController::class, 'update']);
});
