<?php

declare(strict_types=1);

namespace App\Modules\Calendar\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $user_id
 * @property string $course_name
 * @property int $day_of_week
 * @property string $start_time
 * @property string $end_time
 * @property string|null $classroom
 * @property string $color
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
final class ClassScheduleModel extends Model
{
    protected $table = 'class_schedules';

    protected $fillable = [
        'user_id',
        'course_name',
        'day_of_week',
        'start_time',
        'end_time',
        'classroom',
        'color',
    ];

    protected $casts = [
        'day_of_week' => 'integer',
    ];
}
