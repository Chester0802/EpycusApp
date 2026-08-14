<?php

declare(strict_types=1);

namespace App\Modules\Missions\Infrastructure\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $user_id
 * @property string $title
 * @property string|null $description
 * @property string $difficulty
 * @property string $priority
 * @property \Carbon\Carbon|null $due_date
 * @property \Carbon\Carbon|null $completed_at
 * @property int|null $days_early_or_late
 * @property bool $is_overdue
 * @property int $xp_awarded
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 * @property-read Collection<int, SubtaskModel> $subtasks
 */
final class MissionModel extends Model
{
    use SoftDeletes;

    protected $table = 'missions';

    protected $fillable = [
        'user_id', 'title', 'description', 'difficulty', 'priority',
        'due_date', 'completed_at', 'days_early_or_late', 'is_overdue', 'xp_awarded',
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'date:Y-m-d',
            'completed_at' => 'datetime',
            'is_overdue' => 'boolean',
        ];
    }

    /**
     * @return HasMany<SubtaskModel, $this>
     */
    public function subtasks(): HasMany
    {
        return $this->hasMany(SubtaskModel::class, 'mission_id')->orderBy('sort_order');
    }
}
