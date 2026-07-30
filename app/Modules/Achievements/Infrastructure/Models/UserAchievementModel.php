<?php

declare(strict_types=1);

namespace App\Modules\Achievements\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;

final class UserAchievementModel extends Model
{
    protected $table = 'user_achievements';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'achievement_id',
        'unlocked_at',
    ];

    public function achievement()
    {
        return $this->belongsTo(AchievementModel::class, 'achievement_id');
    }
}
