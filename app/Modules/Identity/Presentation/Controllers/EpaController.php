<?php

declare(strict_types=1);

namespace App\Modules\Identity\Presentation\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Identity\Application\DTOs\RecordEpaPretestDTO;
use App\Modules\Identity\Application\UseCases\RecordEpaPretestUseCase;
use App\Modules\Identity\Presentation\Requests\RecordEpaRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

final class EpaController extends Controller
{
    public function __construct(private RecordEpaPretestUseCase $recordEpaPretest) {}

    public function storePretest(RecordEpaRequest $request): RedirectResponse|JsonResponse
    {
        $userId = (int) Auth::id();

        $dto = new RecordEpaPretestDTO(
            userId: $userId,
            item2: (int) $request->input('item_2'),
            item5: (int) $request->input('item_5'),
            item7: (int) $request->input('item_7'),
            item10: (int) $request->input('item_10'),
            item11: (int) $request->input('item_11'),
            item12: (int) $request->input('item_12'),
            item13: (int) $request->input('item_13'),
            item14: (int) $request->input('item_14'),
        );

        try {
            $response = $this->recordEpaPretest->execute($dto);
        } catch (\InvalidArgumentException $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $e->getMessage(),
                ]);
            }

            return redirect()->back()->with('success', 'El diagnóstico inicial EPA ya ha sido completado.');
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => '¡Test inicial EPA registrado exitosamente! Has ganado +50 XP.',
                'total_score' => $response->total_score,
            ]);
        }

        return redirect()->back()->with('success', '¡Test inicial EPA registrado exitosamente! (+50 XP)');

    }
}
