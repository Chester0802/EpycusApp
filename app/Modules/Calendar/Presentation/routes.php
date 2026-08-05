<?php

declare(strict_types=1);

use App\Modules\Calendar\Presentation\Controllers\CalendarController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/calendar', [CalendarController::class, 'index'])->name('calendar.index');
    Route::post('/calendar/schedules', [CalendarController::class, 'storeSchedule'])->name('calendar.schedules.store');
    Route::delete('/calendar/schedules/{id}', [CalendarController::class, 'destroySchedule'])->name('calendar.schedules.destroy');
});

