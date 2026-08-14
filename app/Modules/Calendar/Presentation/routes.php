<?php

declare(strict_types=1);

use App\Modules\Calendar\Presentation\Controllers\CalendarController;
use App\Modules\Calendar\Presentation\Controllers\NoteImageController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])->group(function () {
    // Calendario
    Route::get('/calendar', [CalendarController::class, 'index'])->name('calendar.index');

    // Cursos (reemplaza /calendar/schedules)
    Route::post('/calendar/courses', [CalendarController::class, 'storeCourse'])->name('calendar.courses.store');
    Route::delete('/calendar/courses/{id}', [CalendarController::class, 'destroyCourse'])->name('calendar.courses.destroy');

    // Apuntes
    Route::get('/calendar/courses/{courseId}/note', [CalendarController::class, 'showNote'])->name('calendar.notes.show');
    Route::post('/calendar/courses/{courseId}/note', [CalendarController::class, 'upsertNote'])->name('calendar.notes.upsert');

    // Imágenes de apuntes (controller separado — verifica ownership)
    Route::post('/note-images', [NoteImageController::class, 'store'])->name('note-images.store');
    Route::post('/note-images/capture', [NoteImageController::class, 'capture'])->name('note-images.capture');
    Route::get('/note-images/{id}', [NoteImageController::class, 'show'])->name('note-images.show');
});
