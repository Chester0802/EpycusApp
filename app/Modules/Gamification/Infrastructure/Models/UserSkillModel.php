<?php

declare(strict_types=1);

namespace App\Modules\Gamification\Infrastructure\Models;

use App\Modules\Identity\Infrastructure\Models\UserModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserSkillModel extends Model
{
    protected $table = 'user_skills';
    protected $fillable = ['user_id', 'skill_id', 'level', 'xp'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(UserModel::class);
    }

    public function skill(): BelongsTo
    {
        return $this->belongsTo(SkillModel::class);
    }
}
