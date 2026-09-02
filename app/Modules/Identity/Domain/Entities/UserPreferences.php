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
        private string $wallpaperKey = 'atardecer',
        private ?array $notificationSettings = null,
    ) {}

    public function userId(): UserId
    {
        return $this->userId;
    }

    public function surfaceMode(): SurfaceMode
    {
        return $this->surfaceMode;
    }

    public function wallpaperKey(): string
    {
        return $this->wallpaperKey;
    }

    public function notificationsEnabled(): bool
    {
        return $this->notificationsEnabled;
    }

    public function notificationSettings(): ?array
    {
        return $this->notificationSettings;
    }

    public function changeSurfaceMode(SurfaceMode $surfaceMode): void
    {
        $this->surfaceMode = $surfaceMode;
    }

    public function changeWallpaperKey(string $wallpaperKey): void
    {
        $this->wallpaperKey = $wallpaperKey;
    }

    public function changeNotificationSettings(?array $settings): void
    {
        $this->notificationSettings = $settings;
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
