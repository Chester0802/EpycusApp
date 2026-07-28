<?php

declare(strict_types=1);

use App\Modules\Identity\Presentation\Controllers\ConsentController;
use App\Modules\Identity\Presentation\Controllers\PreferencesController;
use App\Modules\Identity\Presentation\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

// 'web' es obligatorio y explícito acá: a diferencia de routes/web.php,
// este archivo se carga vía IdentityServiceProvider::loadRoutesFrom(), que
// NO hereda el grupo 'web' que bootstrap/app.php aplica automáticamente.
// Sin él, no hay sesión/CSRF en estas rutas y auth() falla en silencio con
// 401 pese a que el usuario esté logueado — encontrado en la Fase 0
// probando de verdad contra un navegador, no con actingAs() de los tests.
Route::middleware(['web', 'auth', 'verified'])->group(function () {
    Route::get('/profile/complete', [ProfileController::class, 'edit'])->name('profile.complete');
    Route::patch('/profile/complete', [ProfileController::class, 'update']);

    Route::post('/consent', [ConsentController::class, 'store'])->name('consent.store');

    Route::patch('/preferences', [PreferencesController::class, 'update'])->name('preferences.update');
});
