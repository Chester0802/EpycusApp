<?php

declare(strict_types=1);

namespace App\Modules\Skills\Infrastructure\Models;

use App\Modules\Identity\Infrastructure\Models\UserModel;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $skill_id
 * @property int $user_id
 * @property int $duration_minutes
 * @property string|null $notes
 * @property int $xp_earned
 * @property Carbon $logged_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
final class SkillLogModel extends Model
{
    protected $table = 'personal_skill_logs';

    protected $fillable = [
        'skill_id',
        'user_id',
        'duration_minutes',
        'notes',
        'xp_earned',
        'logged_at',
    ];

    protected $casts = [
        'duration_minutes' => 'integer',
        'xp_earned' => 'integer',
        'logged_at' => 'date',
    ];

    public function skill(): BelongsTo
    {
        return $this->belongsTo(SkillModel::class, 'skill_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(UserModel::class, 'user_id');
    }
}
