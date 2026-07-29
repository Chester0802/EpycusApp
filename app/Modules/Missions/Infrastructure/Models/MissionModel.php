<?php

declare(strict_types=1);

namespace App\Modules\Missions\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

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

    public function subtasks(): HasMany
    {
        return $this->hasMany(SubtaskModel::class, 'mission_id')->orderBy('sort_order');
    }
}
