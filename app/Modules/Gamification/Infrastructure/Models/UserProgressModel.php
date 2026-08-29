<?php

declare(strict_types=1);

namespace App\Modules\Gamification\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $user_id
 * @property int $total_xp
 * @property int $current_level
 * @property int $current_phase
 * @property int $current_streak
 * @property int $longest_streak
 * @property int $grace_days_left
 * @property string|null $grace_month
 * @property string|null $grace_pending_since
 * @property string|null $last_activity_on
 * @property int $coins
 *
 * `grace_month`/`grace_pending_since`/`last_activity_on` se dejan sin cast
 * de fecha a propósito: AwardXpUseCase y EvaluateStreaksUseCase las
 * comparan como strings `YYYY-MM-DD` (`===`, `>=`) contra otras fechas ya
 * formateadas igual. Agregarles `casts()` a Carbon rompería esas
 * comparaciones silenciosamente.
 */
final class UserProgressModel extends Model
{
    protected $table = 'user_progress';

    protected $primaryKey = 'user_id';

    public $incrementing = false;

    protected $fillable = [
        'user_id',
        'total_xp',
        'current_level',
        'current_phase',
        'current_streak',
        'longest_streak',
        'grace_days_left',
        'grace_month',
        'grace_pending_since',
        'last_activity_on',
        'coins',
    ];

    protected function casts(): array
    {
        return [
            'total_xp' => 'integer',
            'current_level' => 'integer',
            'current_phase' => 'integer',
            'current_streak' => 'integer',
            'longest_streak' => 'integer',
            'grace_days_left' => 'integer',
            'coins' => 'integer',
        ];
    }
}
