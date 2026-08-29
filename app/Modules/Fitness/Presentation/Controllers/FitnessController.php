<?php

declare(strict_types=1);

namespace App\Modules\Fitness\Presentation\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Fitness\Application\UseCases\GetFitnessOverviewUseCase;
use App\Modules\Fitness\Application\UseCases\LogWorkoutUseCase;
use App\Modules\Fitness\Application\UseCases\UpdateHydrationUseCase;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

final class FitnessController extends Controller
{
    public function __construct(
        private readonly GetFitnessOverviewUseCase $getOverview,
        private readonly LogWorkoutUseCase $logWorkout,
        private readonly UpdateHydrationUseCase $updateHydration,
    ) {}

    public function index(): Response
    {
        $userId = (int) Auth::id();
        $overview = $this->getOverview->execute($userId);

        return Inertia::render('Fitness/Index', [
            'overview' => $overview,
        ]);
    }

    public function storeWorkout(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'routine_name' => 'required|string|max:120',
            'duration_minutes' => 'required|integer|min:1|max:300',
            'calories_burned' => 'nullable|integer|min:0|max:2000',
            'notes' => 'nullable|string|max:255',
        ]);

        $userId = (int) Auth::id();

        $result = $this->logWorkout->execute($userId, $validated);

        return back()->with([
            'success' => '¡Entrenamiento registrado con éxito! +25 XP ganados.',
            'xp_awarded' => $result['xp_awarded'],
        ]);
    }

    public function updateHydration(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'delta' => 'required|integer|in:-1,1',
            'date' => 'nullable|date',
        ]);

        $userId = (int) Auth::id();
        $date = $validated['date'] ?? Carbon::now('America/Lima')->toDateString();

        try {
            $result = $this->updateHydration->execute($userId, $date, (int) $validated['delta']);

            if ($request->wantsJson() && ! $request->header('X-Inertia')) {
                return response()->json($result);
            }

            $msg = $result['reached_goal']
                ? '🎉 ¡Meta de hidratación diaria alcanzada! (+20 XP ganados)'
                : 'Registro de agua actualizado.';

            return back()->with('success', $msg);
        } catch (Exception $e) {
            if ($request->wantsJson() && ! $request->header('X-Inertia')) {
                return response()->json(['error' => $e->getMessage()], 422);
            }

            return back()->with('error', $e->getMessage());
        }
    }
}
