<?php

declare(strict_types=1);

namespace App\Modules\Calendar\Infrastructure\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $course_id
 * @property int $day_of_week
 * @property string $start_time
 * @property string $end_time
 * @property string|null $classroom
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read CourseModel $course
 */
final class CourseSessionModel extends Model
{
    protected $table = 'course_sessions';

    protected $fillable = [
        'course_id',
        'day_of_week',
        'start_time',
        'end_time',
        'classroom',
    ];

    protected $casts = [
        'day_of_week' => 'integer',
    ];

    public function course(): BelongsTo
    {
        return $this->belongsTo(CourseModel::class, 'course_id');
    }
}
