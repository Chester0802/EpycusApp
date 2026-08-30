<?php

declare(strict_types=1);

use App\Modules\Calendar\Presentation\Controllers\CalendarController;
use App\Modules\Calendar\Presentation\Controllers\KnowledgeGraphController;
use App\Modules\Calendar\Presentation\Controllers\NoteImageController;
use App\Modules\Calendar\Presentation\Controllers\CoursesController;
use App\Modules\Calendar\Presentation\Controllers\CourseProjectsController;
use App\Modules\Calendar\Presentation\Controllers\CourseGradesController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])->group(function () {
    // Calendario & Time-Blocking
    Route::get('/calendar', [CalendarController::class, 'index'])->name('calendar.index');

    // Grafo de Conocimiento (Segundo Cerebro Asistido por IA)
    Route::get('/api/calendar/knowledge-graph', [KnowledgeGraphController::class, 'show'])->name('calendar.knowledge-graph.show');
    Route::post('/api/calendar/knowledge-graph/generate', [KnowledgeGraphController::class, 'generate'])->name('calendar.knowledge-graph.generate');
    Route::post('/api/calendar/knowledge-graph/positions', [KnowledgeGraphController::class, 'savePositions'])->name('calendar.knowledge-graph.positions');

    // Cursos (Antiguos endpoints del Calendario)
    Route::post('/calendar/courses', [CalendarController::class, 'storeCourse'])->name('calendar.courses.store');
    Route::put('/calendar/courses/{id}', [CalendarController::class, 'updateCourse'])->name('calendar.courses.update');
    Route::delete('/calendar/courses/{id}', [CalendarController::class, 'destroyCourse'])->name('calendar.courses.destroy');

    // Cursos Hub (Fase 2)
    Route::get('/courses', [CoursesController::class, 'index'])->name('courses.index');
    Route::get('/courses/{id}', [CoursesController::class, 'show'])->name('courses.show');
    Route::post('/courses/{id}/syllabus', [CoursesController::class, 'uploadSyllabus'])->name('courses.syllabus.upload');
    Route::delete('/courses/{id}/syllabus', [CoursesController::class, 'deleteSyllabus'])->name('courses.syllabus.delete');
    
    // Proyectos de Curso (Fase 4)
    Route::post('/courses/{courseId}/projects', [CourseProjectsController::class, 'store'])->name('course.projects.store');

    // Notas de Curso (Fase 3)
    Route::post('/courses/{course}/grades', [CourseGradesController::class, 'store'])->name('course.grades.store');
    Route::put('/courses/{course}/grades/{grade}', [CourseGradesController::class, 'update'])->name('course.grades.update');
    Route::delete('/courses/{course}/grades/{grade}', [CourseGradesController::class, 'destroy'])->name('course.grades.destroy');

    // Apuntes
    Route::get('/calendar/courses/{courseId}/note', [CalendarController::class, 'showNote'])->name('calendar.notes.show');
    Route::post('/calendar/courses/{courseId}/note', [CalendarController::class, 'upsertNote'])->name('calendar.notes.upsert');

    // Imágenes de apuntes
    Route::post('/note-images', [NoteImageController::class, 'store'])->name('note-images.store');
    Route::post('/note-images/capture', [NoteImageController::class, 'capture'])->name('note-images.capture');
    Route::get('/note-images/{id}', [NoteImageController::class, 'show'])->name('note-images.show');

    // Day Planner / Time-Blocking Integrado
    Route::post('/calendar/planner/items', [CalendarController::class, 'storePlanItem'])->name('calendar.planner.items.store');
    Route::patch('/calendar/planner/items/{id}/status', [CalendarController::class, 'updatePlanItemStatus'])->name('calendar.planner.items.status');
    Route::put('/calendar/planner/items/{id}', [CalendarController::class, 'updatePlanItem'])->name('calendar.planner.items.update');
    Route::delete('/calendar/planner/items/{id}', [CalendarController::class, 'destroyPlanItem'])->name('calendar.planner.items.destroy');

    Route::post('/calendar/planner/routines', [CalendarController::class, 'storeRoutine'])->name('calendar.planner.routines.store');
    Route::put('/calendar/planner/routines/{id}', [CalendarController::class, 'updateRoutine'])->name('calendar.planner.routines.update');
    Route::delete('/calendar/planner/routines/{id}', [CalendarController::class, 'destroyRoutine'])->name('calendar.planner.routines.destroy');

    Route::post('/calendar/planner/apply-routines', [CalendarController::class, 'applyRoutines'])->name('calendar.planner.routines.apply');
    Route::post('/calendar/planner/load-starter-template', [CalendarController::class, 'loadStarterTemplate'])->name('calendar.planner.starter-template');
});

