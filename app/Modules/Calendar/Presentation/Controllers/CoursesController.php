<?php

declare(strict_types=1);

namespace App\Modules\Calendar\Presentation\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Calendar\Infrastructure\Models\CourseModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

final class CoursesController extends Controller
{
    public function index(Request $request): Response
    {
        $courses = CourseModel::where('user_id', $request->user()->id)
            ->with(['period'])
            ->get();

        return Inertia::render('Courses/Index', [
            'courses' => $courses,
        ]);
    }

    public function show(Request $request, int $id): Response
    {
        $course = CourseModel::where('user_id', $request->user()->id)
            ->with(['period', 'projects.phases.missions', 'gradeEvaluations'])
            ->findOrFail($id);

        return Inertia::render('Courses/Show', [
            'course' => $course,
        ]);
    }

    public function uploadSyllabus(Request $request, int $id)
    {
        $course = CourseModel::where('user_id', $request->user()->id)->findOrFail($id);

        $request->validate([
            'syllabus' => 'required|file|mimes:pdf|max:10240', // Max 10MB
        ]);

        if ($course->syllabus_path) {
            Storage::disk('public')->delete($course->syllabus_path);
        }

        $path = $request->file('syllabus')->store('syllabi', 'public');
        $course->update(['syllabus_path' => $path]);

        return redirect()->back()->with('success', 'Sílabo subido correctamente.');
    }

    public function deleteSyllabus(Request $request, int $id)
    {
        $course = CourseModel::where('user_id', $request->user()->id)->findOrFail($id);

        if ($course->syllabus_path) {
            Storage::disk('public')->delete($course->syllabus_path);
            $course->update(['syllabus_path' => null]);
        }

        return redirect()->back()->with('success', 'Sílabo eliminado.');
    }
}
