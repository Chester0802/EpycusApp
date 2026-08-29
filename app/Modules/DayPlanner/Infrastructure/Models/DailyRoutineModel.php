<?php

declare(strict_types=1);

namespace App\Modules\DayPlanner\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property string $title
 * @property string $time_block
 * @property string $category
 * @property string|null $icon
 * @property int $estimated_minutes
 * @property string|null $scheduled_time
 * @property int $sort_order
 * @property array<int>|null $days_of_week
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
final class DailyRoutineModel extends Model
{
    protected $table = 'daily_routines';

    protected $fillable = [
        'user_id',
        'title',
        'time_block',
        'category',
        'icon',
        'estimated_minutes',
        'scheduled_time',
        'sort_order',
        'days_of_week',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'estimated_minutes' => 'integer',
            'sort_order' => 'integer',
            'days_of_week' => 'array',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return HasMany<DailyPlanItemModel, $this>
     */
    public function planItems(): HasMany
    {
        return $this->hasMany(DailyPlanItemModel::class, 'routine_id');
    }
}
