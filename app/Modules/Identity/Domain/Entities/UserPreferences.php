<?php

declare(strict_types=1);

namespace App\Modules\Identity\Domain\Entities;

use App\Modules\Identity\Domain\ValueObjects\SurfaceMode;
use App\Modules\Identity\Domain\ValueObjects\UserId;

final class UserPreferences
{
    public function __construct(
        private UserId $userId,
        private SurfaceMode $surfaceMode,
        private bool $notificationsEnabled = false,
    ) {}

    public function userId(): UserId
    {
        return $this->userId;
    }

    public function surfaceMode(): SurfaceMode
    {
        return $this->surfaceMode;
    }

    public function notificationsEnabled(): bool
    {
        return $this->notificationsEnabled;
    }

    public function changeSurfaceMode(SurfaceMode $surfaceMode): void
    {
        $this->surfaceMode = $surfaceMode;
    }

    public function enableNotifications(): void
    {
        $this->notificationsEnabled = true;
    }

    public function disableNotifications(): void
    {
        $this->notificationsEnabled = false;
    }
}
