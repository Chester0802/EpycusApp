<?php

declare(strict_types=1);

namespace App\Modules\Calendar\Presentation\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Calendar\Infrastructure\Models\CourseModel;
use App\Modules\Calendar\Infrastructure\Models\UserKnowledgeGraphModel;
use App\Modules\AiAssistant\Application\UseCases\CheckQuotaUseCase;
use App\Modules\Calendar\Domain\Contracts\CalendarRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

final class CoursesController extends Controller
{
    public function __construct(
        private readonly CheckQuotaUseCase $checkQuota,
        private readonly CalendarRepositoryInterface $calendar
    ) {}
    public function index(Request $request): Response
    {
        $courses = CourseModel::where('user_id', $request->user()->id)
            ->with(['period', 'sessions'])
            ->get();

        return Inertia::render('Courses/Index', [
            'courses' => $courses,
        ]);
    }

    public function show(Request $request, int $id): Response
    {
        $userId = (int) $request->user()->id;
        $course = CourseModel::where('user_id', $userId)
            ->with(['period', 'sessions', 'projects.phases.missions.subtasks', 'missions.subtasks', 'gradeEvaluations'])
            ->findOrFail($id);

        $graph = UserKnowledgeGraphModel::where('user_id', $userId)->first();
        
        $courseNodes = [];
        $courseEdges = [];
        $totalMastery = 0;
        $weakChunksCount = 0;

        if ($graph) {
            foreach ($graph->nodes ?? [] as $node) {
                if (($node['course_id'] ?? null) == $id) {
                    $courseNodes[] = $node;
                    $totalMastery += (int) ($node['mastery'] ?? 70);
                    if ((int) ($node['mastery'] ?? 70) < 60) {
                        $weakChunksCount++;
                    }
                }
            }
            foreach ($graph->edges ?? [] as $edge) {
                if (($edge['course_id'] ?? null) == $id) {
                    $courseEdges[] = $edge;
                }
            }
            
            // Re-enrich with connections for the UI
            foreach ($courseNodes as &$n) {
                $nodeId = $n['id'] ?? '';
                $connCount = 0;
                foreach ($courseEdges as $e) {
                    if (($e['source'] ?? '') === $nodeId || ($e['target'] ?? '') === $nodeId) {
                        $connCount++;
                    }
                }
                $n['connections_count'] = $connCount;
                $n['questions_count'] = ! empty($n['quiz_question']) ? 1 : 0;
            }
            unset($n);
        }

        $totalNodes = count($courseNodes);
        $avgMastery = $totalNodes > 0 ? (int) round($totalMastery / $totalNodes) : 0;
        $quota = $this->checkQuota->execute($userId);

        return Inertia::render('Courses/Show', [
            'course' => $course,
            'graphData' => [
                'has_graph' => ! empty($courseNodes),
                'nodes' => $courseNodes,
                'edges' => $courseEdges,
                'stats' => [
                    'total_concepts' => $totalNodes,
                    'total_connections' => count($courseEdges),
                ],
                'last_generated_at' => $graph?->last_generated_at?->format('d/m/Y H:i'),
                'quota' => $quota,
            ],
            'learningStats' => [
                'avgMastery' => $avgMastery,
                'totalChunks' => $totalNodes,
                'weakChunksCount' => $weakChunksCount,
                'streakDays' => 0, // Placeholder, can be integrated later with Habits if needed
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $userId = (int) Auth::id();

        $this->normalizeSessionTimes($request);

        $validated = $request->validate([
            'name'                       => ['required', 'string', 'max:120'],
            'color'                      => ['nullable', 'string', 'max:50'],
            'professor'                  => ['nullable', 'string', 'max:120'],
            'credits'                    => ['nullable', 'integer', 'min:0', 'max:50'],
            'target_grade'               => ['nullable', 'numeric', 'min:0', 'max:20'],
            'min_pass_grade'             => ['nullable', 'numeric', 'min:0', 'max:20'],
            'starts_at'                  => ['nullable', 'date'],
            'ends_at'                    => [
                'nullable',
                'date',
                \Illuminate\Validation\Rule::when($request->filled('starts_at'), ['after_or_equal:starts_at']),
            ],
            'sessions'                   => ['required', 'array', 'min:1', 'max:7'],
            'sessions.*.day_of_week'     => ['required', 'integer', 'between:1,7'],
            'sessions.*.start_time'      => ['required', 'date_format:H:i,H:i:s'],
            'sessions.*.end_time'        => ['required', 'date_format:H:i,H:i:s', 'after:sessions.*.start_time'],
            'sessions.*.classroom'       => ['nullable', 'string', 'max:60'],
        ]);

        $this->calendar->createCourse($userId, $validated);

        return back()->with('success', 'Curso registrado correctamente.');
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $userId = (int) Auth::id();

        $this->normalizeSessionTimes($request);

        $validated = $request->validate([
            'name'                       => ['required', 'string', 'max:120'],
            'color'                      => ['nullable', 'string', 'max:50'],
            'professor'                  => ['nullable', 'string', 'max:120'],
            'credits'                    => ['nullable', 'integer', 'min:0', 'max:50'],
            'target_grade'               => ['nullable', 'numeric', 'min:0', 'max:20'],
            'min_pass_grade'             => ['nullable', 'numeric', 'min:0', 'max:20'],
            'starts_at'                  => ['nullable', 'date'],
            'ends_at'                    => [
                'nullable',
                'date',
                \Illuminate\Validation\Rule::when($request->filled('starts_at'), ['after_or_equal:starts_at']),
            ],
            'sessions'                   => ['required', 'array', 'min:1', 'max:7'],
            'sessions.*.day_of_week'     => ['required', 'integer', 'between:1,7'],
            'sessions.*.start_time'      => ['required', 'date_format:H:i,H:i:s'],
            'sessions.*.end_time'        => ['required', 'date_format:H:i,H:i:s', 'after:sessions.*.start_time'],
            'sessions.*.classroom'       => ['nullable', 'string', 'max:60'],
        ]);

        $this->calendar->updateCourse($userId, $id, $validated);

        return back()->with('success', 'Curso actualizado correctamente.');
    }

    private function normalizeSessionTimes(Request $request): void
    {
        if ($request->has('sessions') && is_array($request->sessions)) {
            $sessions = $request->sessions;
            foreach ($sessions as &$s) {
                if (isset($s['start_time']) && is_string($s['start_time'])) {
                    $parts = explode(':', trim($s['start_time']));
                    if (count($parts) >= 2) {
                        $s['start_time'] = sprintf('%02d:%02d', (int) $parts[0], (int) $parts[1]);
                    }
                }
                if (isset($s['end_time']) && is_string($s['end_time'])) {
                    $parts = explode(':', trim($s['end_time']));
                    if (count($parts) >= 2) {
                        $s['end_time'] = sprintf('%02d:%02d', (int) $parts[0], (int) $parts[1]);
                    }
                }
            }
            $request->merge(['sessions' => $sessions]);
        }
    }

    public function destroy(int $id): RedirectResponse
    {
        $userId = (int) Auth::id();
        $this->calendar->deleteCourse($userId, $id);

        return back()->with('success', 'Curso eliminado.');
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
