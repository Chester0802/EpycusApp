<?php

declare(strict_types=1);

namespace App\Modules\Identity\Infrastructure\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $user_id
 * @property string $wallpaper_key
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
final class UserUnlockedWallpaperModel extends Model
{
    protected $table = 'user_unlocked_wallpapers';

    protected $fillable = [
        'user_id',
        'wallpaper_key',
    ];
}
