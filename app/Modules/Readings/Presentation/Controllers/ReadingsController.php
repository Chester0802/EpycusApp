<?php

declare(strict_types=1);

namespace App\Modules\Readings\Presentation\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Gamification\Application\UseCases\AwardXpUseCase;
use App\Modules\Readings\Infrastructure\Models\ReadingModel;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

final class ReadingsController extends Controller
{
    public function __construct(
        private readonly AwardXpUseCase $awardXp,
    ) {}

    public function index(): Response
    {
        $userId = (int) Auth::id();

        $readings = ReadingModel::forUser($userId)
            ->with(['tags'])
            ->orderByDesc('updated_at')
            ->get()
            ->map(function ($r) {
                $progressPercent = ($r->total_pages && $r->total_pages > 0)
                    ? min(100, (int) round(($r->current_page / $r->total_pages) * 100))
                    : 0;

                return [
                    'id' => $r->id,
                    'title' => $r->title,
                    'author' => $r->author,
                    'year' => $r->year,
                    'type' => $r->type,
                    'total_pages' => $r->total_pages,
                    'isbn' => $r->isbn,
                    'cover_url' => $r->cover_url,
                    'status' => $r->status,
                    'current_page' => $r->current_page,
                    'progress_percent' => $progressPercent,
                    'rating' => $r->rating,
                    'started_at' => $r->started_at?->toDateString(),
                    'finished_at' => $r->finished_at?->toDateString(),
                    'tags' => $r->tags->pluck('tag')->toArray(),
                ];
            });

        $activeReadings = $readings->filter(fn ($r) => $r['status'] === 'reading')->values();
        $finishedReadings = $readings->filter(fn ($r) => $r['status'] === 'finished')->values();
        $wantToRead = $readings->filter(fn ($r) => $r['status'] === 'want_to_read')->values();
        $pausedReadings = $readings->filter(fn ($r) => in_array($r['status'], ['paused', 'dropped'], true))->values();

        $totalPagesRead = $readings->sum('current_page');
        $finishedCountThisYear = $finishedReadings->filter(function ($r) {
            return $r['finished_at'] && substr($r['finished_at'], 0, 4) === date('Y');
        })->count();

        return Inertia::render('Readings/Index', [
            'readings' => $readings,
            'activeReadings' => $activeReadings,
            'wantToRead' => $wantToRead,
            'finishedReadings' => $finishedReadings,
            'pausedReadings' => $pausedReadings,
            'stats' => [
                'total_readings' => $readings->count(),
                'active_count' => $activeReadings->count(),
                'finished_this_year' => $finishedCountThisYear,
                'total_pages_read' => $totalPagesRead,
            ],
            'xp_awarded' => session()->pull('xp_awarded', 0),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $userId = (int) Auth::id();

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'author' => ['nullable', 'string', 'max:200'],
            'year' => ['nullable', 'integer', 'min:1000', 'max:2100'],
            'type' => ['required', 'in:book_fiction,book_nonfiction,academic_article,thesis,manual,other'],
            'total_pages' => ['nullable', 'integer', 'min:1', 'max:10000'],
            'isbn' => ['nullable', 'string', 'max:30'],
            'cover_url' => ['nullable', 'string', 'max:500'],
            'status' => ['required', 'in:want_to_read,reading,finished,paused,dropped'],
            'current_page' => ['nullable', 'integer', 'min:0'],
            'rating' => ['nullable', 'integer', 'min:1', 'max:5'],
            'tags' => ['nullable', 'array'],
        ]);

        $currentPage = (int) ($validated['current_page'] ?? 0);
        $status = $validated['status'];
        $startedAt = $status === 'reading' ? Carbon::now('America/Lima')->toDateString() : null;
        $finishedAt = $status === 'finished' ? Carbon::now('America/Lima')->toDateString() : null;

        $reading = ReadingModel::create([
            'user_id' => $userId,
            'title' => $validated['title'],
            'author' => $validated['author'] ?? null,
            'year' => $validated['year'] ?? null,
            'type' => $validated['type'],
            'total_pages' => $validated['total_pages'] ?? null,
            'isbn' => $validated['isbn'] ?? null,
            'cover_url' => $validated['cover_url'] ?? null,
            'status' => $status,
            'current_page' => $currentPage,
            'rating' => $validated['rating'] ?? null,
            'started_at' => $startedAt,
            'finished_at' => $finishedAt,
        ]);

        if (!empty($validated['tags']) && is_array($validated['tags'])) {
            foreach ($validated['tags'] as $tag) {
                $cleanTag = trim((string) $tag);
                if ($cleanTag !== '') {
                    $reading->tags()->create(['tag' => $cleanTag]);
                }
            }
        }

        return back()->with('success', 'Lectura agregada a tu biblioteca.');
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $userId = (int) Auth::id();
        $reading = ReadingModel::where('id', $id)->where('user_id', $userId)->firstOrFail();

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'author' => ['nullable', 'string', 'max:200'],
            'year' => ['nullable', 'integer', 'min:1000', 'max:2100'],
            'type' => ['required', 'in:book_fiction,book_nonfiction,academic_article,thesis,manual,other'],
            'total_pages' => ['nullable', 'integer', 'min:1', 'max:10000'],
            'isbn' => ['nullable', 'string', 'max:30'],
            'cover_url' => ['nullable', 'string', 'max:500'],
            'status' => ['required', 'in:want_to_read,reading,finished,paused,dropped'],
            'current_page' => ['nullable', 'integer', 'min:0'],
            'rating' => ['nullable', 'integer', 'min:1', 'max:5'],
            'tags' => ['nullable', 'array'],
        ]);

        $reading->update([
            'title' => $validated['title'],
            'author' => $validated['author'] ?? null,
            'year' => $validated['year'] ?? null,
            'type' => $validated['type'],
            'total_pages' => $validated['total_pages'] ?? null,
            'isbn' => $validated['isbn'] ?? null,
            'cover_url' => $validated['cover_url'] ?? null,
            'status' => $validated['status'],
            'current_page' => (int) ($validated['current_page'] ?? $reading->current_page),
            'rating' => $validated['rating'] ?? null,
        ]);

        if (isset($validated['tags']) && is_array($validated['tags'])) {
            $reading->tags()->delete();
            foreach ($validated['tags'] as $tag) {
                $cleanTag = trim((string) $tag);
                if ($cleanTag !== '') {
                    $reading->tags()->create(['tag' => $cleanTag]);
                }
            }
        }

        return back()->with('success', 'Lectura actualizada.');
    }

    public function updateProgress(Request $request, int $id): JsonResponse
    {
        $userId = (int) Auth::id();
        $reading = ReadingModel::where('id', $id)->where('user_id', $userId)->firstOrFail();

        $validated = $request->validate([
            'current_page' => ['required', 'integer', 'min:0'],
        ]);

        $newPage = $validated['current_page'];
        $pagesAdvanced = max(0, $newPage - $reading->current_page);
        $reading->current_page = $newPage;

        $xpEarned = 0;
        $isFinished = false;

        if ($reading->total_pages && $newPage >= $reading->total_pages && $reading->status !== 'finished') {
            $reading->status = 'finished';
            $reading->finished_at = Carbon::now('America/Lima')->toDateString();
            $isFinished = true;
            $xpEarned = 50; // Bonus por terminar libro
        } elseif ($pagesAdvanced > 0) {
            $xpEarned = 15; // XP por sesión de lectura
        }

        $reading->save();

        if ($xpEarned > 0) {
            $this->awardXp->execute($userId, 'reading', $reading->id, $xpEarned, 100, true);
        }

        return response()->json([
            'message' => $isFinished ? '¡Felicitaciones! Has completado esta lectura 🏆 (+50 XP)' : 'Progreso actualizado (+15 XP)',
            'reading' => $reading,
            'xp_awarded' => $xpEarned,
            'is_finished' => $isFinished,
        ]);
    }

    public function destroy(int $id): RedirectResponse
    {
        $userId = (int) Auth::id();
        $reading = ReadingModel::where('id', $id)->where('user_id', $userId)->firstOrFail();
        $reading->delete();

        return back()->with('success', 'Lectura eliminada.');
    }
}
