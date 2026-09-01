<?php

declare(strict_types=1);

namespace App\Modules\Shop\Presentation\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Shop\Application\UseCases\CreateRewardUseCase;
use App\Modules\Shop\Application\UseCases\DeleteRewardUseCase;
use App\Modules\Shop\Application\UseCases\GetShopDataUseCase;
use App\Modules\Shop\Application\UseCases\MarkRewardUsedUseCase;
use App\Modules\Shop\Application\UseCases\RedeemRewardUseCase;
use App\Modules\Shop\Application\UseCases\UpdateRewardUseCase;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

final class ShopController extends Controller
{
    public function __construct(
        private readonly GetShopDataUseCase $getShopData,
        private readonly RedeemRewardUseCase $redeemReward,
        private readonly CreateRewardUseCase $createReward,
        private readonly UpdateRewardUseCase $updateReward,
        private readonly DeleteRewardUseCase $deleteReward,
        private readonly MarkRewardUsedUseCase $markUsed,
    ) {}

    public function index(): Response
    {
        $userId = (int) Auth::id();
        $data = $this->getShopData->execute($userId);

        return Inertia::render('Shop/Index', [
            'coins' => $data['coins'],
            'rewards' => $data['rewards'],
            'redemptions' => $data['redemptions'],
            'templates' => $data['templates'],
        ]);
    }

    public function redeem(Request $request, int $id): JsonResponse|RedirectResponse
    {
        $userId = (int) Auth::id();

        try {
            $result = $this->redeemReward->execute($userId, $id);

            if ($request->wantsJson() && ! $request->header('X-Inertia')) {
                return response()->json($result);
            }

            return back()->with('success', $result['message']);
        } catch (Exception $e) {
            if ($request->wantsJson() && ! $request->header('X-Inertia')) {
                return response()->json(['error' => $e->getMessage()], 422);
            }

            return back()->with('error', $e->getMessage());
        }
    }

    public function storeReward(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:160',
            'cost_coins' => 'required|integer|min:10|max:10000',
            'icon' => 'nullable|string|max:20',
            'category' => 'required|string|max:40',
        ]);

        $userId = (int) Auth::id();

        $this->createReward->execute($userId, $validated);

        return back()->with('success', 'Recompensa creada exitosamente.');
    }

    public function updateReward(Request $request, int $id): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:160',
            'cost_coins' => 'sometimes|required|integer|min:10|max:10000',
            'icon' => 'nullable|string|max:20',
            'category' => 'sometimes|required|string|max:40',
        ]);

        $userId = (int) Auth::id();

        try {
            $this->updateReward->execute($id, $userId, $validated);

            return back()->with('success', 'Recompensa actualizada.');
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function destroyReward(int $id): RedirectResponse
    {
        $userId = (int) Auth::id();

        try {
            $this->deleteReward->execute($id, $userId);

            return back()->with('success', 'Recompensa eliminada.');
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function markUsed(int $id): RedirectResponse
    {
        $userId = (int) Auth::id();

        try {
            $this->markUsed->execute($id, $userId);

            return back()->with('success', '¡Disfrutaste tu recompensa!');
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function reviewRedemption(Request $request, int $id): JsonResponse
    {
        $userId = (int) Auth::id();
        $redemption = \App\Modules\Shop\Infrastructure\Models\RewardRedemptionModel::where('id', $id)
            ->where('user_id', $userId)
            ->firstOrFail();

        $validated = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'review_text' => ['nullable', 'string', 'max:1000'],
            'entertainment_title' => ['nullable', 'string', 'max:200'],
            'entertainment_category' => ['nullable', 'in:series,movie,anime,videogame,book,other'],
        ]);

        $redemption->update([
            'rating' => $validated['rating'],
            'review_text' => $validated['review_text'] ?? null,
            'entertainment_title' => $validated['entertainment_title'] ?? $redemption->entertainment_title,
            'entertainment_category' => $validated['entertainment_category'] ?? $redemption->entertainment_category,
            'status' => 'used',
            'used_at' => now(),
        ]);

        return response()->json([
            'message' => '¡Reseña guardada con éxito!',
            'redemption' => $redemption,
        ]);
    }
}
