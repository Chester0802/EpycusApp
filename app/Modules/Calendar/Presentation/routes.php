<?php

declare(strict_types=1);

use App\Modules\Calendar\Presentation\Controllers\CalendarController;
use App\Modules\Calendar\Presentation\Controllers\KnowledgeGraphController;
use App\Modules\Calendar\Presentation\Controllers\NoteImageController;
use App\Modules\Calendar\Presentation\Controllers\CoursesController;
use App\Modules\Calendar\Presentation\Controllers\CourseProjectsController;
use App\Modules\Calendar\Presentation\Controllers\CourseGradesController;
use App\Modules\Calendar\Presentation\Controllers\CourseLearningController;
use App\Modules\Calendar\Presentation\Controllers\FlashcardsController;
use App\Modules\Calendar\Presentation\Controllers\NotesController;
use App\Modules\Calendar\Presentation\Controllers\PersonalEventsController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])->group(function () {
    // Eventos Personales (Fase 4 - Calendario Total)
    Route::post('/api/calendar/personal-events', [PersonalEventsController::class, 'store'])->name('calendar.personal-events.store');
    Route::put('/api/calendar/personal-events/{id}', [PersonalEventsController::class, 'update'])->name('calendar.personal-events.update');
    Route::delete('/api/calendar/personal-events/{id}', [PersonalEventsController::class, 'destroy'])->name('calendar.personal-events.destroy');

    // Flashcards y Simulacro de Examen (Fase 3)
    Route::get('/api/courses/{course}/flashcards', [FlashcardsController::class, 'index'])->name('courses.flashcards.index');
    Route::post('/api/courses/{course}/flashcards', [FlashcardsController::class, 'store'])->name('courses.flashcards.store');
    Route::put('/api/flashcards/{id}', [FlashcardsController::class, 'update'])->name('flashcards.update');
    Route::delete('/api/flashcards/{id}', [FlashcardsController::class, 'destroy'])->name('flashcards.destroy');
    Route::post('/api/flashcards/{id}/review', [FlashcardsController::class, 'review'])->name('flashcards.review');
    Route::post('/api/courses/{course}/flashcards/generate-ai', [FlashcardsController::class, 'generateFromAi'])->name('courses.flashcards.generate-ai');
    Route::post('/api/courses/{course}/mock-exam/generate', [FlashcardsController::class, 'generateMockExam'])->name('courses.mock-exam.generate');
    Route::post('/api/courses/{course}/mock-exam/evaluate', [FlashcardsController::class, 'evaluateMockExam'])->name('courses.mock-exam.evaluate');

    // Calendario & Time-Blocking
    // Vista principal de apuntes
    Route::get('/notes', [NotesController::class, 'index'])->name('notes.index');

    Route::get('/calendar', [CalendarController::class, 'index'])->name('calendar.index');

    // Grafo de Conocimiento (Segundo Cerebro Asistido por IA)
    Route::get('/api/calendar/knowledge-graph', [KnowledgeGraphController::class, 'show'])->name('calendar.knowledge-graph.show');
    Route::post('/api/calendar/knowledge-graph/generate', [KnowledgeGraphController::class, 'generate'])->name('calendar.knowledge-graph.generate');
    Route::post('/api/calendar/knowledge-graph/positions', [KnowledgeGraphController::class, 'savePositions'])->name('calendar.knowledge-graph.positions');

    // Cursos (Antiguos endpoints del Calendario, ahora en CoursesController)
    Route::post('/calendar/courses', [CoursesController::class, 'store'])->name('calendar.courses.store');
    Route::put('/calendar/courses/{id}', [CoursesController::class, 'update'])->name('calendar.courses.update');
    Route::delete('/calendar/courses/{id}', [CoursesController::class, 'destroy'])->name('calendar.courses.destroy');

    // Cursos Hub (Fase 2)
    Route::get('/courses', [CoursesController::class, 'index'])->name('courses.index');
    Route::get('/courses/{id}', [CoursesController::class, 'show'])->name('courses.show');
    Route::post('/courses/{id}/syllabus', [CoursesController::class, 'uploadSyllabus'])->name('courses.syllabus.upload');
    Route::delete('/courses/{id}/syllabus', [CoursesController::class, 'deleteSyllabus'])->name('courses.syllabus.delete');
    
    // Zona de Aprendizaje en Cursos (Fase 6)
    Route::post('/api/courses/{course}/learning/generate-graph', [CourseLearningController::class, 'generateGraph'])->name('courses.learning.generate-graph');
    Route::post('/api/courses/{course}/learning/chunk/mastery', [CourseLearningController::class, 'updateChunkMastery'])->name('courses.learning.chunk.mastery');
    Route::post('/api/courses/{course}/learning/generate-mission', [CourseLearningController::class, 'generateMission'])->name('courses.learning.generate-mission');
    
    // Proyectos de Curso (Fase 4)
    Route::post('/courses/{courseId}/projects', [CourseProjectsController::class, 'store'])->name('course.projects.store');
    Route::post('/courses/{courseId}/projects/{projectId}/phases', [CourseProjectsController::class, 'storePhase'])->name('course.projects.phases.store');
    Route::put('/courses/{courseId}/projects/{projectId}/phases/{phaseId}', [CourseProjectsController::class, 'updatePhase'])->name('course.projects.phases.update');
    Route::delete('/courses/{courseId}/projects/{projectId}/phases/{phaseId}', [CourseProjectsController::class, 'destroyPhase'])->name('course.projects.phases.destroy');

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

