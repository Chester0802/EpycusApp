<?php

declare(strict_types=1);

namespace App\Modules\Missions\Infrastructure\Models;

use App\Modules\Pomodoro\Infrastructure\Models\PomodoroSessionSubtaskModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $mission_id
 * @property string $title
 * @property bool $is_completed
 * @property \Carbon\Carbon|null $completed_at
 * @property int $sort_order
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @property-read MissionModel $mission
 */
final class SubtaskModel extends Model
{
    protected $table = 'subtasks';

    protected $fillable = [
        'mission_id', 'title', 'is_completed', 'completed_at', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_completed' => 'boolean',
            'completed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<MissionModel, $this>
     */
    public function mission(): BelongsTo
    {
        return $this->belongsTo(MissionModel::class, 'mission_id');
    }

    /**
     * @return HasMany<PomodoroSessionSubtaskModel, $this>
     */
    public function pomodoroCompletions(): HasMany
    {
        return $this->hasMany(PomodoroSessionSubtaskModel::class, 'subtask_id');
    }
}
