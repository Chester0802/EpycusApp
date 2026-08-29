<?php

declare(strict_types=1);

namespace App\Modules\AiAssistant\Presentation\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\AiAssistant\Application\UseCases\CheckQuotaUseCase;
use App\Modules\AiAssistant\Application\UseCases\GetConversationHistoryUseCase;
use App\Modules\AiAssistant\Application\UseCases\ListUserConversationsUseCase;
use App\Modules\AiAssistant\Application\UseCases\RateAdviceUseCase;
use App\Modules\AiAssistant\Application\UseCases\SendConsultationUseCase;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

final class AiAssistantController extends Controller
{
    public function __construct(
        private readonly SendConsultationUseCase $sendConsultation,
        private readonly GetConversationHistoryUseCase $getHistory,
        private readonly ListUserConversationsUseCase $listConversations,
        private readonly CheckQuotaUseCase $checkQuota,
        private readonly RateAdviceUseCase $rateAdvice,
    ) {}

    public function index(): Response
    {
        $userId = (int) Auth::id();
        $user = Auth::user();

        $history = $this->getHistory->execute($userId);
        $conversations = $this->listConversations->execute($userId);
        $quota = $this->checkQuota->execute($userId);

        return Inertia::render('AiAssistant/Index', [
            'initialConversationId' => $history['conversation_id'],
            'initialMessages' => $history['messages'],
            'conversations' => $conversations,
            'quota' => $quota,
            'avatarStyle' => $user?->avatar_style ?? 'base',
            'avatarGender' => $user?->avatar_gender ?? 'm',
        ]);
    }

    public function getConversationMessages(int $id): JsonResponse
    {
        $userId = (int) Auth::id();
        $history = $this->getHistory->execute($userId, $id);

        return response()->json([
            'success' => true,
            'data' => $history,
        ]);
    }

    public function consult(Request $request): JsonResponse
    {
        $userId = (int) Auth::id();

        $request->validate([
            'message' => ['required', 'string', 'max:1000'],
            'conversation_id' => ['nullable', 'integer'],
        ]);

        try {
            $result = $this->sendConsultation->execute(
                $userId,
                $request->input('message'),
                $request->input('conversation_id') ? (int) $request->input('conversation_id') : null
            );

            return response()->json([
                'success' => true,
                'data' => $result,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function rate(Request $request): JsonResponse
    {
        $userId = (int) Auth::id();

        $request->validate([
            'message_id' => ['required', 'integer'],
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
        ]);

        try {
            $this->rateAdvice->execute(
                $userId,
                (int) $request->input('message_id'),
                (int) $request->input('rating')
            );

            return response()->json([
                'success' => true,
                'message' => 'Valoración guardada.',
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
