<?php

declare(strict_types=1);

namespace App\Modules\Habits\Presentation\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Habits\Application\DTOs\CreateHabitDTO;
use App\Modules\Habits\Application\DTOs\UpdateHabitDTO;
use App\Modules\Habits\Application\UseCases\ArchiveHabitUseCase;
use App\Modules\Habits\Application\UseCases\CreateHabitUseCase;
use App\Modules\Habits\Application\UseCases\DeleteHabitUseCase;
use App\Modules\Habits\Application\UseCases\ToggleHabitCompletionUseCase;
use App\Modules\Habits\Application\UseCases\UnarchiveHabitUseCase;
use App\Modules\Habits\Application\UseCases\UpdateHabitUseCase;
use App\Modules\Habits\Domain\Contracts\HabitRepositoryInterface;
use App\Shared\Domain\Contracts\UserProgressReaderInterface;
use App\Shared\Domain\Services\AvatarAssetResolver;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

final class HabitsController extends Controller
{
    public function __construct(
        private HabitRepositoryInterface $repository,
        private CreateHabitUseCase $createHabit,
        private UpdateHabitUseCase $updateHabit,
        private ToggleHabitCompletionUseCase $toggleCompletion,
        private DeleteHabitUseCase $deleteHabit,
        private ArchiveHabitUseCase $archiveHabit,
        private UnarchiveHabitUseCase $unarchiveHabit,
        private UserProgressReaderInterface $progress,
    ) {}

    public function index(): Response
    {
        $userId = (int) Auth::id();
        /** @var \App\Modules\Identity\Infrastructure\Models\UserModel|null $user */
        $user = Auth::user();
        $habits = $this->repository->getActiveForUser($userId);
        $today = Carbon::now()->toDateString();
        $weekStart = Carbon::now()->startOfWeek()->toDateString();
        $monthStart = Carbon::now()->startOfMonth()->toDateString();

        $habitsData = $habits->map(function ($habit) use ($today, $weekStart, $monthStart) {
            $completedToday = $habit->completions->contains(fn ($c) => $c->completed_for === $today);

            $completedDates = $habit->completions->pluck('completed_for')->toArray();

            $streak = $this->calculateStreak($completedDates, $today);

            $weeklyCount = collect($completedDates)->filter(fn ($d) => $d >= $weekStart)->count();
            $monthlyCount = collect($completedDates)->filter(fn ($d) => $d >= $monthStart)->count();

            return [
                'id' => $habit->id,
                'title' => $habit->title,
                'category' => $habit->category,
                'frequency' => $habit->frequency,
                'icon' => $habit->icon,
                'is_completed_today' => $completedToday,
                'completions_count' => $habit->completions->count(),
                'streak' => $streak,
                'weekly_count' => $weeklyCount,
                'monthly_count' => $monthlyCount,
                'completed_dates' => $completedDates,
                'created_at' => $habit->created_at?->toDateString(),
            ];
        });

        $totalWeekly = collect($habitsData)->sum('weekly_count');
        $totalStreak = collect($habitsData)->max('streak');

        $archived = $this->repository->getArchivedForUser($userId);
        $archivedData = $archived->map(fn ($h) => [
            'id' => $h->id,
            'title' => $h->title,
            'category' => $h->category,
            'frequency' => $h->frequency,
            'icon' => $h->icon,
            'created_at' => $h->created_at?->toDateString(),
        ]);

        return Inertia::render('Habits/Index', [
            'habits' => $habitsData,
            'archivedHabits' => $archivedData,
            'todayDate' => $today,
            'xp_awarded' => session()->pull('xp_awarded', 0),
            'stats' => [
                'total_weekly' => $totalWeekly,
                'max_streak' => $totalStreak,
                'active_habits' => $habitsData->count(),
            ],
            'avatarStyle' => $user ? $user->avatar_style : 'base',
            'avatarGender' => $user ? $user->avatar_gender : 'm',
            'progress' => [
                'phase' => $this->progress->getPhaseFor($userId),
            ],
        ]);
    }

    /**
     * @param  list<string>  $completedDates
     */
    private function calculateStreak(array $completedDates, string $today): int
    {
        $dates = collect($completedDates)->map(fn ($d) => Carbon::parse($d))->sortByDesc('timestamp');

        if ($dates->isEmpty()) {
            return 0;
        }

        $streak = 0;
        $expected = Carbon::parse($today);

        foreach ($dates as $date) {
            if ($date->toDateString() === $expected->toDateString()) {
                $streak++;
                $expected->subDay();
            } elseif ($date->toDateString() === $expected->copy()->subDay()->toDateString()) {
                // allowed one skip (today not completed yet, so yesterday counts)
                $streak++;
                $expected = $expected->subDay();
            } else {
                break;
            }
        }

        return $streak;
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:120',
            'category' => 'required|in:estudio,sueno,ejercicio,alimentacion,otro',
            'frequency' => 'required|array',
            'icon' => 'nullable|string|max:40',
        ]);

        $userId = (int) Auth::id();

        $dto = new CreateHabitDTO(
            userId: $userId,
            title: $validated['title'],
            category: $validated['category'],
            frequency: $validated['frequency'],
            icon: $validated['icon'] ?? null
        );

        $this->createHabit->execute($dto);

        return back()->with('success', 'Hábito creado correctamente.');
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:120',
            'category' => 'required|in:estudio,sueno,ejercicio,alimentacion,otro',
            'frequency' => 'required|array',
            'icon' => 'nullable|string|max:40',
        ]);

        $userId = (int) Auth::id();

        $dto = new UpdateHabitDTO(
            habitId: $id,
            userId: $userId,
            title: $validated['title'],
            category: $validated['category'],
            frequency: $validated['frequency'],
            icon: $validated['icon'] ?? null,
        );

        try {
            $this->updateHabit->execute($dto);

            return back()->with('success', 'Hábito actualizado correctamente.');
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function toggle(Request $request, int $id): JsonResponse|RedirectResponse
    {
        $userId = (int) Auth::id();
        $date = $request->input('date', Carbon::now()->toDateString());

        // El XP real se lee de Gamification por diferencia de totales, no
        // se le pregunta directamente cuánto otorgó: `ToggleHabitCompletionUseCase`
        // dispara el evento de forma síncrona, así que al volver de
        // `execute()` el total ya quedó actualizado (o sin cambios, si
        // tocó tope o era un reintento — ver AwardXpUseCase). Esto evita
        // inventar un método nuevo en `UserProgressReaderInterface`
        // (docs/01-MODULOS.md §6 ya define esa interfaz con 4 getters
        // exactos; no hacía falta un quinto).
        $xpBefore = $this->progress->getTotalXpFor($userId);
        $result = $this->toggleCompletion->execute($id, $userId, is_string($date) ? $date : null);
        $xpAfter = $this->progress->getTotalXpFor($userId);

        $xpAwarded = max(0, $xpAfter - $xpBefore);

        if ($request->wantsJson() && ! $request->header('X-Inertia')) {
            return response()->json([
                'completed' => $result['completed'],
                'xp_awarded' => $xpAwarded,
            ]);
        }

        return back()->with('xp_awarded', $xpAwarded);
    }

    public function destroy(int $id): RedirectResponse
    {
        $userId = (int) Auth::id();
        $habit = $this->repository->findByIdAndUser($id, $userId);

        if ($habit) {
            $this->deleteHabit->execute($habit);
        }

        return back()->with('success', 'Hábito eliminado.');
    }

    public function archive(int $id): RedirectResponse
    {
        $userId = (int) Auth::id();
        $habit = $this->repository->findByIdAndUser($id, $userId);

        if ($habit) {
            $this->archiveHabit->execute($habit);
        }

        return back()->with('success', 'Hábito archivado.');
    }

    public function unarchive(int $id): RedirectResponse
    {
        $userId = (int) Auth::id();
        $habit = $this->repository->findByIdAndUser($id, $userId);

        if ($habit) {
            $this->unarchiveHabit->execute($habit);
        }

        return back()->with('success', 'Hábito restaurado.');
    }
}
