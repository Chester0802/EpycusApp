<?php

declare(strict_types=1);

namespace App\Modules\Missions\Infrastructure\Models;

use App\Modules\Pomodoro\Infrastructure\Models\PomodoroSessionSubtaskModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    public function mission(): BelongsTo
    {
        return $this->belongsTo(MissionModel::class, 'mission_id');
    }

    public function pomodoroCompletions(): HasMany
    {
        return $this->hasMany(PomodoroSessionSubtaskModel::class, 'subtask_id');
    }
}
