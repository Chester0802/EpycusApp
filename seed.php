<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$user = App\Models\User::where('email', 'test1@epycus.es')->first();
if (!$user) { echo "User not found"; exit; }

$course = App\Modules\Calendar\Infrastructure\Models\CourseModel::create([
    'user_id' => $user->id,
    'name' => 'Física Cuántica',
    'color' => '#8b5cf6',
    'professor' => 'Dr. Emmett Brown',
    'credits' => 4,
    'target_grade' => 18
]);

$project = App\Modules\Calendar\Infrastructure\Models\CourseProjectModel::create([
    'user_id' => $user->id,
    'course_id' => $course->id,
    'title' => 'Máquina del Tiempo',
    'description' => 'Construir un condensador de flujo'
]);

App\Modules\Calendar\Infrastructure\Models\ProjectPhaseModel::create([
    'course_project_id' => $project->id,
    'name' => 'Fase 1: Diseño',
    'color' => '#3b82f6',
    'order' => 0
]);

App\Modules\Calendar\Infrastructure\Models\ProjectPhaseModel::create([
    'course_project_id' => $project->id,
    'name' => 'Fase 2: Pruebas',
    'color' => '#10b981',
    'order' => 1
]);

echo "Success!";
