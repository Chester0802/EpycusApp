<?php

declare(strict_types=1);

namespace App\Modules\AiAssistant\Application\UseCases;

use App\Modules\AiAssistant\Infrastructure\Models\AiConversationModel;

final class GetConversationHistoryUseCase
{
    public function execute(int $userId, ?int $conversationId = null): array
    {
        $query = AiConversationModel::with('messages')
            ->where('user_id', $userId);

        if ($conversationId !== null) {
            $query->where('id', $conversationId);
        } else {
            $query->latest('updated_at');
        }

        $conversation = $query->first();

        if (! $conversation) {
            return [
                'conversation_id' => null,
                'messages' => [],
            ];
        }

        return [
            'conversation_id' => $conversation->id,
            'messages' => $conversation->messages->map(fn ($msg) => [
                'id' => $msg->id,
                'role' => $msg->role,
                'content' => $msg->content,
                'category' => $msg->category,
                'rating' => $msg->rating,
                'created_at' => $msg->created_at?->toIso8601String(),
            ])->toArray(),
        ];
    }
}
