<?php

declare(strict_types=1);

namespace App\Modules\Calendar\Presentation\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Calendar\Infrastructure\Models\CourseModel;
use App\Modules\Calendar\Infrastructure\Models\CourseProjectModel;
use App\Modules\Calendar\Infrastructure\Models\ProjectPhaseModel;
use Illuminate\Http\Request;

use App\Modules\Missions\Infrastructure\Models\MissionModel;
use Illuminate\Http\RedirectResponse;

final class CourseProjectsController extends Controller
{
    public function store(Request $request, int $courseId): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'phases' => 'array',
            'phases.*.name' => 'required|string',
            'phases.*.color' => 'required|string',
        ]);

        $course = CourseModel::where('user_id', $request->user()->id)->findOrFail($courseId);

        $project = CourseProjectModel::create([
            'user_id' => $request->user()->id,
            'course_id' => $course->id,
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
        ]);

        if (!empty($validated['phases'])) {
            foreach ($validated['phases'] as $idx => $phase) {
                ProjectPhaseModel::create([
                    'course_project_id' => $project->id,
                    'name' => $phase['name'],
                    'color' => $phase['color'],
                    'order' => $idx,
                ]);
            }
        }

        return redirect()->back()->with('success', 'Proyecto creado correctamente.');
    }

    public function storePhase(Request $request, int $courseId, int $projectId): RedirectResponse
    {
        $course = CourseModel::where('user_id', $request->user()->id)->findOrFail($courseId);
        $project = CourseProjectModel::where('course_id', $course->id)->findOrFail($projectId);

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'color' => 'required|string|max:30',
        ]);

        $maxOrder = ProjectPhaseModel::where('course_project_id', $project->id)->max('order');
        $order = ($maxOrder !== null) ? $maxOrder + 1 : 0;

        ProjectPhaseModel::create([
            'course_project_id' => $project->id,
            'name' => $validated['name'],
            'color' => $validated['color'],
            'order' => $order,
        ]);

        return redirect()->back()->with('success', 'Fase creada exitosamente.');
    }

    public function updatePhase(Request $request, int $courseId, int $projectId, int $phaseId): RedirectResponse
    {
        $course = CourseModel::where('user_id', $request->user()->id)->findOrFail($courseId);
        $project = CourseProjectModel::where('course_id', $course->id)->findOrFail($projectId);
        $phase = ProjectPhaseModel::where('course_project_id', $project->id)->findOrFail($phaseId);

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'color' => 'required|string|max:30',
        ]);

        $phase->update($validated);

        return redirect()->back()->with('success', 'Fase actualizada.');
    }

    public function destroyPhase(Request $request, int $courseId, int $projectId, int $phaseId): RedirectResponse
    {
        $course = CourseModel::where('user_id', $request->user()->id)->findOrFail($courseId);
        $project = CourseProjectModel::where('course_id', $course->id)->findOrFail($projectId);
        $phase = ProjectPhaseModel::where('course_project_id', $project->id)->findOrFail($phaseId);

        // Desvincular misiones asociadas a esta fase para no perderlas
        MissionModel::where('project_phase_id', $phase->id)->update(['project_phase_id' => null]);

        $phase->delete();

        return redirect()->back()->with('success', 'Fase eliminada.');
    }
}
