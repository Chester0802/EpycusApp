<?php

declare(strict_types=1);

namespace App\Modules\Identity\Infrastructure\Models;

use Database\Factories\UserPreferencesFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $user_id
 * @property string $surface_mode
 * @property bool $notifications_enabled
 */
final class UserPreferencesModel extends Model
{
    /** @use HasFactory<UserPreferencesFactory> */
    use HasFactory;

    protected $table = 'user_preferences';

    protected $fillable = [
        'user_id',
        'surface_mode',
        'wallpaper_key',
        'notifications_enabled',
    ];

    protected function casts(): array
    {
        return [
            'notifications_enabled' => 'boolean',
        ];
    }

    /**
     * UserPreferencesModel no vive en App\Models, así que la convención de
     * nombres de HasFactory no encuentra la factory sola.
     */
    protected static function newFactory(): UserPreferencesFactory
    {
        return UserPreferencesFactory::new();
    }
}
