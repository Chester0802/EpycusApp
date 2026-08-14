<?php

declare(strict_types=1);

namespace App\Modules\AiAssistant\Application\UseCases;

use App\Modules\AiAssistant\Infrastructure\Models\AiMessageModel;
use Exception;

final class RateAdviceUseCase
{
    public function execute(int $userId, int $messageId, int $rating): void
    {
        $rating = max(1, min(5, $rating));

        $message = AiMessageModel::whereHas('conversation', function ($query) use ($userId) {
            $query->where('user_id', $userId);
        })->where('id', $messageId)->first();

        if (! $message) {
            throw new Exception('Mensaje no encontrado.');
        }

        $message->update([
            'rating' => $rating,
        ]);
    }
}
