<?php

declare(strict_types=1);

namespace App\Modules\AiAssistant\Application\UseCases;

use App\Modules\AiAssistant\Infrastructure\Models\AiConversationModel;

final class ListUserConversationsUseCase
{
    public function execute(int $userId): array
    {
        return AiConversationModel::where('user_id', $userId)
            ->with(['messages' => fn ($q) => $q->latest()->limit(1)])
            ->latest('updated_at')
            ->get()
            ->map(function ($conv) {
                $lastMsg = $conv->messages->first()?->content ?? 'Sin mensajes';
                if (mb_strlen($lastMsg) > 35) {
                    $lastMsg = mb_substr($lastMsg, 0, 35) . '...';
                }

                return [
                    'id' => $conv->id,
                    'title' => $conv->title,
                    'last_message' => $lastMsg,
                    'updated_at' => $conv->updated_at?->format('d/m H:i'),
                ];
            })
            ->toArray();
    }
}
