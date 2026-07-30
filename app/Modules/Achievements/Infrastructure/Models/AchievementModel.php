<?php

declare(strict_types=1);

namespace App\Modules\Achievements\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;

final class AchievementModel extends Model
{
    protected $table = 'achievements';

    protected $fillable = [
        'code',
        'name',
        'description',
        'category',
        'icon',
        'xp_reward',
        'wallpaper_reward_key',
    ];

    protected $casts = [
        'xp_reward' => 'integer',
    ];
}
