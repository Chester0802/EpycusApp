<?php

declare(strict_types=1);

namespace App\Modules\DayPlanner\Presentation\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\DayPlanner\Application\UseCases\CreateDayPlanItemUseCase;
use App\Modules\DayPlanner\Application\UseCases\DeleteDayPlanItemUseCase;
use App\Modules\DayPlanner\Application\UseCases\GetOrGenerateDailyPlanUseCase;
use App\Modules\DayPlanner\Application\UseCases\SaveDailyRoutinesUseCase;
use App\Modules\DayPlanner\Application\UseCases\UpdateDayPlanItemStatusUseCase;
use App\Modules\DayPlanner\Application\UseCases\UpdateDayPlanItemUseCase;
use App\Shared\Domain\Contracts\UserProgressReaderInterface;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

final class DayPlannerController extends Controller
{
    public function __construct(
        private readonly GetOrGenerateDailyPlanUseCase $getPlan,
        private readonly UpdateDayPlanItemStatusUseCase $updateStatus,
        private readonly CreateDayPlanItemUseCase $createItem,
        private readonly UpdateDayPlanItemUseCase $updateItem,
        private readonly DeleteDayPlanItemUseCase $deleteItem,
        private readonly SaveDailyRoutinesUseCase $routinesUseCase,
        private readonly UserProgressReaderInterface $progress,
    ) {}

    public function index(Request $request): Response
    {
        $userId = (int) Auth::id();
        /** @var \App\Modules\Identity\Infrastructure\Models\UserModel|null $user */
        $user = Auth::user();
        $date = $request->query('date', Carbon::now('America/Lima')->toDateString());

        $planData = $this->getPlan->execute($userId, is_string($date) ? $date : null);

        return Inertia::render('DayPlanner/Index', [
            'plan' => $planData,
            'currentDate' => $date,
            'xp_awarded' => session()->pull('xp_awarded', 0),
            'avatarStyle' => $user?->avatar_style ?? 'base',
            'avatarGender' => $user?->avatar_gender ?? 'm',
            'progress' => [
                'total_xp' => $this->progress->getTotalXpFor($userId),
                'phase' => $this->progress->getPhaseFor($userId),
                'streak' => $this->progress->getCurrentStreakFor($userId),
            ],
        ]);
    }

    public function updateItemStatus(Request $request, int $id): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,done,skipped,postponed',
            'skip_reason' => 'nullable|string|max:80',
            'postpone_to_block' => 'nullable|in:morning,afternoon,night,anytime',
        ]);

        $userId = (int) Auth::id();

        try {
            $result = $this->updateStatus->execute(
                itemId: $id,
                userId: $userId,
                status: $validated['status'],
                skipReason: $validated['skip_reason'] ?? null,
                postponeToBlock: $validated['postpone_to_block'] ?? null,
            );

            if ($request->wantsJson() && ! $request->header('X-Inertia')) {
                return response()->json($result);
            }

            return back()->with([
                'success' => $this->getStatusMessage($validated['status']),
                'xp_awarded' => $result['xp_awarded'],
            ]);
        } catch (Exception $e) {
            if ($request->wantsJson() && ! $request->header('X-Inertia')) {
                return response()->json(['error' => $e->getMessage()], 422);
            }

            return back()->with('error', $e->getMessage());
        }
    }

    public function storeItem(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'plan_date' => 'required|date',
            'title' => 'required|string|max:160',
            'category' => 'required|string|max:40',
            'time_block' => 'required|in:morning,afternoon,night,anytime',
            'scheduled_time' => 'nullable|string|max:10',
            'estimated_minutes' => 'nullable|integer|min:1|max:480',
            'notes' => 'nullable|string|max:500',
        ]);

        $userId = (int) Auth::id();

        $this->createItem->execute($userId, $validated);

        return back()->with('success', 'Actividad añadida al plan del día.');
    }

    public function updateItemDetails(Request $request, int $id): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:160',
            'category' => 'sometimes|required|string|max:40',
            'time_block' => 'sometimes|required|in:morning,afternoon,night,anytime',
            'scheduled_time' => 'nullable|string|max:10',
            'estimated_minutes' => 'nullable|integer|min:1|max:480',
            'notes' => 'nullable|string|max:500',
        ]);

        $userId = (int) Auth::id();

        try {
            $this->updateItem->execute($id, $userId, $validated);

            return back()->with('success', 'Actividad actualizada.');
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function destroyItem(int $id): RedirectResponse
    {
        $userId = (int) Auth::id();

        try {
            $this->deleteItem->execute($id, $userId);

            return back()->with('success', 'Actividad eliminada del plan.');
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function storeRoutine(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:160',
            'category' => 'required|string|max:40',
            'time_block' => 'required|in:morning,afternoon,night,anytime',
            'scheduled_time' => 'nullable|string|max:10',
            'estimated_minutes' => 'nullable|integer|min:1|max:480',
            'days_of_week' => 'nullable|array',
        ]);

        $userId = (int) Auth::id();

        $this->routinesUseCase->create($userId, $validated);

        return back()->with('success', 'Plantilla de rutina guardada.');
    }

    public function updateRoutine(Request $request, int $id): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:160',
            'category' => 'sometimes|required|string|max:40',
            'time_block' => 'sometimes|required|in:morning,afternoon,night,anytime',
            'scheduled_time' => 'nullable|string|max:10',
            'estimated_minutes' => 'nullable|integer|min:1|max:480',
            'days_of_week' => 'nullable|array',
            'is_active' => 'sometimes|boolean',
        ]);

        $userId = (int) Auth::id();

        $this->routinesUseCase->update($id, $userId, $validated);

        return back()->with('success', 'Plantilla de rutina actualizada.');
    }

    public function destroyRoutine(int $id): RedirectResponse
    {
        $userId = (int) Auth::id();

        $this->routinesUseCase->delete($id, $userId);

        return back()->with('success', 'Plantilla de rutina eliminada.');
    }

    private function getStatusMessage(string $status): string
    {
        return match ($status) {
            'done' => '¡Excelente! Actividad marcada como completada.',
            'skipped' => 'Actividad saltada registrada.',
            'postponed' => 'Actividad postergada al siguiente bloque.',
            default => 'Estado actualizado.',
        };
    }
}
