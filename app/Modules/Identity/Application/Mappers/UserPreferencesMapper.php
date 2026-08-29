<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application\Mappers;

use App\Modules\Identity\Domain\Entities\UserPreferences;
use App\Modules\Identity\Domain\ValueObjects\SurfaceMode;
use App\Modules\Identity\Domain\ValueObjects\UserId;
use App\Modules\Identity\Infrastructure\Models\UserPreferencesModel;

final class UserPreferencesMapper
{
    public function toDomain(UserPreferencesModel $model): UserPreferences
    {
        return new UserPreferences(
            userId: new UserId($model->user_id),
            surfaceMode: new SurfaceMode($model->surface_mode),
            notificationsEnabled: $model->notifications_enabled,
            wallpaperKey: $model->wallpaper_key ?? 'atardecer',
        );
    }

    /** @return array<string, mixed> */
    public function toPersistence(UserPreferences $preferences): array
    {
        return [
            'user_id' => $preferences->userId()->value(),
            'surface_mode' => $preferences->surfaceMode()->value(),
            'wallpaper_key' => $preferences->wallpaperKey(),
            'notifications_enabled' => $preferences->notificationsEnabled(),
        ];
    }
}
