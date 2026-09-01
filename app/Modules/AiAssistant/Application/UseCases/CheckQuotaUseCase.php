<?php

declare(strict_types=1);

namespace App\Modules\AiAssistant\Application\UseCases;

use App\Modules\AiAssistant\Infrastructure\Models\AiQuotaModel;
use Carbon\Carbon;

final class CheckQuotaUseCase
{
    public function execute(int $userId): array
    {
        $today = Carbon::now('America/Lima')->toDateString();
        $maxQuota = (int) config('services.deepseek.quota', 5);

        $quotaRecord = AiQuotaModel::where('user_id', $userId)
            ->where('date', $today)
            ->first();

        $usedCount = $quotaRecord !== null ? (int) $quotaRecord->used_count : 0;
        $remaining = max(0, $maxQuota - $usedCount);

        return [
            'max_quota' => $maxQuota,
            'used_count' => $usedCount,
            'remaining' => $remaining,
            'is_exhausted' => $remaining <= 0,
        ];
    }
}
