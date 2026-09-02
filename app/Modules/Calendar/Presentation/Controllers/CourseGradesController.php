<?php

namespace App\Modules\Calendar\Presentation\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Calendar\Infrastructure\Models\CourseModel;
use App\Modules\Calendar\Infrastructure\Models\GradeEvaluationModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CourseGradesController extends Controller
{
    public function store(Request $request, CourseModel $course)
    {
        if ($course->user_id !== Auth::id()) {
            abort(403);
        }

        if ($request->input('obtained_score') === '' || $request->input('obtained_score') === null) {
            $request->merge(['obtained_score' => null]);
        }
        if (!$request->filled('max_score')) {
            $request->merge(['max_score' => 20]);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:80',
            'weight' => 'required|numeric|min:0|max:100',
            'obtained_score' => 'nullable|numeric|min:0',
            'max_score' => 'nullable|numeric|min:1',
            'eval_date' => 'nullable|date',
        ]);

        $validated['max_score'] = $validated['max_score'] ?? 20;

        $course->gradeEvaluations()->create($validated);

        return redirect()->back()->with('success', 'Evaluación añadida.');
    }

    public function update(Request $request, CourseModel $course, GradeEvaluationModel $grade)
    {
        if ($course->user_id !== Auth::id() || $grade->course_id !== $course->id) {
            abort(403);
        }

        if ($request->input('obtained_score') === '' || $request->input('obtained_score') === null) {
            $request->merge(['obtained_score' => null]);
        }
        if (!$request->filled('max_score')) {
            $request->merge(['max_score' => 20]);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:80',
            'weight' => 'required|numeric|min:0|max:100',
            'obtained_score' => 'nullable|numeric|min:0',
            'max_score' => 'nullable|numeric|min:1',
            'eval_date' => 'nullable|date',
        ]);

        $validated['max_score'] = $validated['max_score'] ?? 20;

        $grade->update($validated);

        return redirect()->back()->with('success', 'Evaluación actualizada.');
    }

    public function destroy(CourseModel $course, GradeEvaluationModel $grade)
    {
        if ($course->user_id !== Auth::id() || $grade->course_id !== $course->id) {
            abort(403);
        }

        $grade->delete();

        return redirect()->back()->with('success', 'Evaluación eliminada.');
    }
}
