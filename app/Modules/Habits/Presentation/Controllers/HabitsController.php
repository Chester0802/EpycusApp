<?php

declare(strict_types=1);

namespace App\Modules\Habits\Presentation\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Habits\Application\DTOs\CreateHabitDTO;
use App\Modules\Habits\Application\UseCases\CreateHabitUseCase;
use App\Modules\Habits\Application\UseCases\DeleteHabitUseCase;
use App\Modules\Habits\Application\UseCases\ToggleHabitCompletionUseCase;
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
        private ToggleHabitCompletionUseCase $toggleCompletion,
        private DeleteHabitUseCase $deleteHabit,
        private UserProgressReaderInterface $progress,
        private AvatarAssetResolver $avatars,
    ) {}

    public function index(): Response
    {
        $userId = (int) Auth::id();
        $user = Auth::user();
        $habits = $this->repository->getActiveForUser($userId);
        $today = Carbon::now()->toDateString();

        $habitsData = $habits->map(function ($habit) use ($today) {
            $completedToday = $habit->completions->contains(fn ($c) => $c->completed_for === $today);

            return [
                'id' => $habit->id,
                'title' => $habit->title,
                'category' => $habit->category,
                'frequency' => $habit->frequency,
                'icon' => $habit->icon,
                'is_completed_today' => $completedToday,
                'completions_count' => $habit->completions->count(),
            ];
        });

        return Inertia::render('Habits/Index', [
            'habits' => $habitsData,
            'todayDate' => $today,
            // Personaje fijo de Hábitos (orden=2, ver AvatarAssetResolver).
            'avatarImage' => $this->avatars->imageForModule($user?->avatar_style, $user?->avatar_gender, 'habits'),
        ]);
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

        $payload = [
            'completed' => $result['completed'],
            'xp_awarded' => max(0, $xpAfter - $xpBefore),
        ];

        if ($request->wantsJson() && ! $request->header('X-Inertia')) {
            return response()->json($payload);
        }

        return back();
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
}
