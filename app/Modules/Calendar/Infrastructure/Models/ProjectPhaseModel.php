<?php

declare(strict_types=1);

namespace App\Modules\Calendar\Infrastructure\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Modules\Missions\Infrastructure\Models\MissionModel;

/**
 * @property int $id
 * @property int $course_project_id
 * @property string $name
 * @property string $color
 * @property int $order
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
final class ProjectPhaseModel extends Model
{
    protected $table = 'project_phases';

    protected $fillable = [
        'course_project_id',
        'name',
        'color',
        'order',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(CourseProjectModel::class, 'course_project_id');
    }

    public function missions(): HasMany
    {
        return $this->hasMany(MissionModel::class, 'project_phase_id');
    }
}
