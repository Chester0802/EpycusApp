<?php

declare(strict_types=1);

namespace App\Modules\Calendar\Presentation\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Calendar\Infrastructure\Models\CourseModel;
use App\Modules\Calendar\Infrastructure\Models\CourseProjectModel;
use App\Modules\Calendar\Infrastructure\Models\ProjectPhaseModel;
use Illuminate\Http\Request;

final class CourseProjectsController extends Controller
{
    public function store(Request $request, int $courseId)
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

        return redirect()->back();
    }
}
