<?php

declare(strict_types=1);

namespace App\Modules\Motivation\Application\UseCases;

use App\Modules\Motivation\Infrastructure\Models\UsageTipModel;
use App\Modules\Motivation\Infrastructure\Models\UserTipViewModel;
use App\Shared\Domain\Services\NoRepeatPicker;

final class GetTipForModuleUseCase
{
    public function __construct(
        private readonly NoRepeatPicker $picker
    ) {}

    public function execute(int $userId, string $moduleKey): ?array
    {
        $allTips = UsageTipModel::where('module_key', $moduleKey)->get();
        if ($allTips->isEmpty()) {
            return null;
        }

        $allTipIds = $allTips->pluck('id')->toArray();

        // Obtener tips descartados o ya mostrados por este usuario
        $dismissedTipIds = UserTipViewModel::where('user_id', $userId)
            ->whereIn('tip_id', $allTipIds)
            ->where('is_dismissed', true)
            ->pluck('tip_id')
            ->toArray();

        $selectedId = $this->picker->pick($allTipIds, $dismissedTipIds);
        if (! $selectedId) {
            return null;
        }

        $tip = $allTips->firstWhere('id', $selectedId);

        // Registrar vista si no existe
        UserTipViewModel::firstOrCreate([
            'user_id' => $userId,
            'tip_id' => $selectedId,
        ]);

        return [
            'id' => $tip->id,
            'module_key' => $tip->module_key,
            'content' => $tip->content,
        ];
    }
}
