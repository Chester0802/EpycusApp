<?php

declare(strict_types=1);

namespace App\Modules\DayPlanner\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property string $plan_date
 * @property int|null $routine_id
 * @property string $title
 * @property string $category
 * @property string $time_block
 * @property string|null $scheduled_time
 * @property int $estimated_minutes
 * @property string $status
 * @property string|null $skip_reason
 * @property string|null $postponed_to_block
 * @property int $postponed_count
 * @property int $xp_awarded
 * @property int $coins_awarded
 * @property int $sort_order
 * @property int|null $linked_habit_id
 * @property int|null $linked_mission_id
 * @property string|null $notes
 * @property Carbon|null $completed_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read DailyRoutineModel|null $routine
 */
final class DailyPlanItemModel extends Model
{
    protected $table = 'daily_plan_items';

    protected $fillable = [
        'user_id',
        'plan_date',
        'routine_id',
        'title',
        'category',
        'time_block',
        'scheduled_time',
        'estimated_minutes',
        'status',
        'skip_reason',
        'postponed_to_block',
        'postponed_count',
        'xp_awarded',
        'coins_awarded',
        'sort_order',
        'linked_habit_id',
        'linked_mission_id',
        'notes',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'plan_date' => 'date:Y-m-d',
            'estimated_minutes' => 'integer',
            'postponed_count' => 'integer',
            'xp_awarded' => 'integer',
            'coins_awarded' => 'integer',
            'sort_order' => 'integer',
            'completed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<DailyRoutineModel, $this>
     */
    public function routine(): BelongsTo
    {
        return $this->belongsTo(DailyRoutineModel::class, 'routine_id');
    }
}
