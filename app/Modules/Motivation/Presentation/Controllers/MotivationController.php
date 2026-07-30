<?php

declare(strict_types=1);

namespace App\Modules\Motivation\Presentation\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Motivation\Application\UseCases\DismissTipUseCase;
use App\Modules\Motivation\Application\UseCases\GetTipForModuleUseCase;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

final class MotivationController extends Controller
{
    public function __construct(
        private readonly GetTipForModuleUseCase $getTip,
        private readonly DismissTipUseCase $dismissTip,
    ) {}

    public function getTip(string $module): JsonResponse
    {
        $userId = (int) Auth::id();
        $tip = $this->getTip->execute($userId, $module);

        return response()->json([
            'success' => true,
            'data' => $tip,
        ]);
    }

    public function dismissTip(Request $request): JsonResponse
    {
        $userId = (int) Auth::id();
        $request->validate([
            'tip_id' => ['required', 'integer'],
        ]);

        try {
            $this->dismissTip->execute($userId, (int) $request->input('tip_id'));

            return response()->json([
                'success' => true,
                'message' => 'Tip descartado.',
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
