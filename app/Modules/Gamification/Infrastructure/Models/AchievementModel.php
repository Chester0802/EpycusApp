<?php

declare(strict_types=1);

namespace App\Modules\Gamification\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;

class AchievementModel extends Model
{
    protected $table = 'achievements';
    protected $fillable = ['name', 'description', 'icon', 'condition', 'reward_xp'];
}
