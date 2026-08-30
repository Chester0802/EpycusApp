<?php

declare(strict_types=1);

namespace App\Modules\Missions\Infrastructure\Models;

use App\Modules\Calendar\Infrastructure\Models\CourseModel;
use App\Modules\Calendar\Infrastructure\Models\ProjectPhaseModel;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $user_id
 * @property int|null $course_id
 * @property string $title
 * @property string|null $description
 * @property string $difficulty
 * @property string $priority
 * @property string $eisenhower_quadrant
 * @property string $mission_type
 * @property int|null $project_phase_id
 * @property \Carbon\Carbon|null $planned_date
 * @property string|null $planned_start
 * @property string|null $planned_end
 * @property \Carbon\Carbon|null $due_date
 * @property \Carbon\Carbon|null $completed_at
 * @property int|null $days_early_or_late
 * @property bool $is_overdue
 * @property int $xp_awarded
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 * @property-read Collection<int, SubtaskModel> $subtasks
 * @property-read CourseModel|null $course
 * @property-read ProjectPhaseModel|null $projectPhase
 */
final class MissionModel extends Model
{
    use SoftDeletes;

    protected $table = 'missions';

    protected $fillable = [
        'user_id', 'course_id', 'mission_type', 'project_phase_id', 'title', 'description', 'difficulty', 'priority', 'eisenhower_quadrant',
        'due_date', 'planned_date', 'planned_start', 'planned_end', 'completed_at', 'days_early_or_late', 'is_overdue', 'xp_awarded',
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'date:Y-m-d',
            'planned_date' => 'date:Y-m-d',
            'completed_at' => 'datetime',
            'is_overdue' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<CourseModel, $this>
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(CourseModel::class, 'course_id');
    }

    /**
     * @return HasMany<SubtaskModel, $this>
     */
    public function subtasks(): HasMany
    {
        return $this->hasMany(SubtaskModel::class, 'mission_id')->orderBy('sort_order');
    }

    /**
     * @return BelongsTo<ProjectPhaseModel, $this>
     */
    public function projectPhase(): BelongsTo
    {
        return $this->belongsTo(ProjectPhaseModel::class, 'project_phase_id');
    }
}
