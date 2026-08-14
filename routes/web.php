<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function (\Illuminate\Http\Request $request) {
    if (str_starts_with($request->getHost(), 'app.')) {
        if (\Illuminate\Support\Facades\Auth::check()) {
            return redirect()->route('dashboard');
        }

        return redirect()->route('login');
    }

    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

// Si se accede a cualquier ruta de app (login, register, dashboard) desde epycus.es, redirigir a app.epycus.es
Route::matched(function (\Illuminate\Routing\Events\RouteMatched $event) {
    $request = request();
    $path = $request->path();
    if ($request->getHost() === 'epycus.es' && $path !== '/' && $path !== 'terms') {
        header('Location: https://app.epycus.es'.$request->getRequestUri());
        exit;
    }
});



Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::patch('/profile/avatar', [ProfileController::class, 'updateAvatarOptions'])->name('profile.avatar.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::get('/terms', function () {
    return Inertia::render('Terms');
})->name('terms');

require __DIR__.'/auth.php';
