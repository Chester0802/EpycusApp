<?php

declare(strict_types=1);

namespace App\Modules\Calendar\Infrastructure\Models;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Modules\Missions\Infrastructure\Models\MissionModel;

/**
 * @property int $id
 * @property int $user_id
 * @property int $course_id
 * @property string $title
 * @property string|null $description
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
final class CourseProjectModel extends Model
{
    protected $table = 'course_projects';

    protected $fillable = [
        'user_id',
        'course_id',
        'title',
        'description',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(CourseModel::class, 'course_id');
    }

    public function phases(): HasMany
    {
        return $this->hasMany(ProjectPhaseModel::class, 'course_project_id')->orderBy('order');
    }
}
