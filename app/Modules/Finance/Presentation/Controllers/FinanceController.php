<?php

declare(strict_types=1);

namespace App\Modules\Finance\Presentation\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Finance\Application\UseCases\CreateSavingsGoalUseCase;
use App\Modules\Finance\Application\UseCases\CreateTransactionUseCase;
use App\Modules\Finance\Application\UseCases\DeleteSavingsGoalUseCase;
use App\Modules\Finance\Application\UseCases\DeleteTransactionUseCase;
use App\Modules\Finance\Application\UseCases\DepositSavingsUseCase;
use App\Modules\Finance\Application\UseCases\GetFinanceOverviewUseCase;
use App\Modules\Finance\Application\UseCases\SetBudgetUseCase;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

final class FinanceController extends Controller
{
    public function __construct(
        private readonly GetFinanceOverviewUseCase $getOverview,
        private readonly CreateTransactionUseCase $createTransaction,
        private readonly DeleteTransactionUseCase $deleteTransaction,
        private readonly SetBudgetUseCase $setBudget,
        private readonly CreateSavingsGoalUseCase $createGoal,
        private readonly DepositSavingsUseCase $depositSavings,
        private readonly DeleteSavingsGoalUseCase $deleteGoal,
    ) {}

    public function index(Request $request): Response
    {
        $userId = (int) Auth::id();
        $month = $request->integer('month', Carbon::now('America/Lima')->month);
        $year = $request->integer('year', Carbon::now('America/Lima')->year);

        $overview = $this->getOverview->execute($userId, $month, $year);

        return Inertia::render('Finance/Index', [
            'overview' => $overview,
        ]);
    }

    public function storeTransaction(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'type' => 'required|in:expense,income',
            'amount' => 'required|numeric|min:0.1|max:100000',
            'category' => 'required|string|max:40',
            'date' => 'required|date',
            'notes' => 'nullable|string|max:255',
        ]);

        $userId = (int) Auth::id();

        $this->createTransaction->execute($userId, $validated);

        return back()->with('success', 'Movimiento registrado correctamente.');
    }

    public function destroyTransaction(int $id): RedirectResponse
    {
        $userId = (int) Auth::id();

        try {
            $this->deleteTransaction->execute($id, $userId);

            return back()->with('success', 'Movimiento eliminado.');
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function saveBudget(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'month' => 'required|integer|between:1,12',
            'year' => 'required|integer|between:2020,2050',
            'category' => 'required|string|max:40',
            'monthly_limit' => 'required|numeric|min:1|max:100000',
        ]);

        $userId = (int) Auth::id();

        $this->setBudget->execute(
            $userId,
            (int) $validated['month'],
            (int) $validated['year'],
            $validated['category'],
            (float) $validated['monthly_limit']
        );

        return back()->with('success', 'Presupuesto mensual actualizado.');
    }

    public function storeSavingsGoal(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:160',
            'target_amount' => 'required|numeric|min:1|max:100000',
            'current_amount' => 'nullable|numeric|min:0|max:100000',
            'target_date' => 'nullable|date',
            'reward_xp' => 'nullable|integer|min:10|max:1000',
        ]);

        $userId = (int) Auth::id();

        $this->createGoal->execute($userId, $validated);

        return back()->with('success', 'Meta de ahorro establecida.');
    }

    public function depositToSavings(Request $request, int $id): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.5|max:100000',
        ]);

        $userId = (int) Auth::id();

        try {
            $result = $this->depositSavings->execute($id, $userId, (float) $validated['amount']);

            if ($request->wantsJson() && ! $request->header('X-Inertia')) {
                return response()->json($result);
            }

            $msg = $result['just_completed']
                ? "🎉 ¡Felicidades! Meta de ahorro alcanzada (+{$result['xp_awarded']} XP ganados)."
                : 'Aporte al ahorro guardado exitosamente.';

            return back()->with('success', $msg);
        } catch (Exception $e) {
            if ($request->wantsJson() && ! $request->header('X-Inertia')) {
                return response()->json(['error' => $e->getMessage()], 422);
            }

            return back()->with('error', $e->getMessage());
        }
    }

    public function destroySavingsGoal(int $id): RedirectResponse
    {
        $userId = (int) Auth::id();

        try {
            $this->deleteGoal->execute($id, $userId);

            return back()->with('success', 'Meta de ahorro eliminada.');
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
