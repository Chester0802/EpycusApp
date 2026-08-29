<?php

declare(strict_types=1);

use App\Modules\Villains\Presentation\Controllers\VillainController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/villains', [VillainController::class, 'index'])->name('villains.index');
});
