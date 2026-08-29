<?php

declare(strict_types=1);

use App\Modules\Identity\Presentation\Controllers\ConsentController;
use App\Modules\Identity\Presentation\Controllers\EpaController;
use App\Modules\Identity\Presentation\Controllers\PreferencesController;
use App\Modules\Identity\Presentation\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

// 'web' es obligatorio y explícito acá: a diferencia de routes/web.php,
// este archivo se carga vía IdentityServiceProvider::loadRoutesFrom(), que
// NO hereda el grupo 'web' que bootstrap/app.php aplica automáticamente.
// Sin él, no hay sesión/CSRF en estas rutas y auth() falla en silencio con
// 401 pese a que el usuario esté logueado — encontrado en la Fase 0
// probando de verdad contra un navegador, no con actingAs() de los tests.
Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/profile/complete', [ProfileController::class, 'edit'])->name('profile.complete');
    Route::patch('/profile/complete', [ProfileController::class, 'update']);

    // GET muestra la pantalla de consentimiento; POST lo registra.
    // Ambos necesitaban existir — el GET faltaba en el controlador original.
    Route::get('/consent', [ConsentController::class, 'show'])->name('consent.show');
    Route::post('/consent', [ConsentController::class, 'store'])->name('consent.store');

    Route::post('/epa/pretest', [EpaController::class, 'storePretest'])->name('epa.pretest.store');

    Route::get('/settings', [PreferencesController::class, 'edit'])->name('settings.edit');
    Route::patch('/preferences', [PreferencesController::class, 'update'])->name('preferences.update');
    Route::post('/preferences/wallpaper/unlock', [PreferencesController::class, 'unlockWallpaper'])->name('preferences.wallpaper.unlock');
    Route::post('/preferences/wallpaper/select', [PreferencesController::class, 'selectWallpaper'])->name('preferences.wallpaper.select');
});
