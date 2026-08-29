<?php

declare(strict_types=1);

namespace App\Modules\Motivation\Application\UseCases;

use App\Modules\Motivation\Infrastructure\Models\UserTipViewModel;

final class DismissTipUseCase
{
    public function execute(int $userId, int $tipId): void
    {
        UserTipViewModel::updateOrCreate(
            ['user_id' => $userId, 'tip_id' => $tipId],
            ['is_dismissed' => true]
        );
    }
}
