<?php

declare(strict_types=1);

namespace App\Modules\Pomodoro\Application\UseCases;

use App\Modules\Pomodoro\Application\DTOs\ActiveSessionResultDTO;

final class GetActiveSessionUseCase
{
    public function __construct(private ResolveStaleSessionUseCase $resolveStale) {}

    public function execute(int $userId): ActiveSessionResultDTO
    {
        return $this->resolveStale->execute($userId);
    }
}
