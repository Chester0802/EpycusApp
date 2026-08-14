<?php

declare(strict_types=1);

namespace App\Modules\Calendar\Infrastructure\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property string $color
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, CourseSessionModel> $sessions
 * @property-read CourseNoteModel|null $note
 */
final class CourseModel extends Model
{
    protected $table = 'courses';

    protected $fillable = [
        'user_id',
        'name',
        'color',
    ];

    public function sessions(): HasMany
    {
        return $this->hasMany(CourseSessionModel::class, 'course_id')
            ->orderBy('day_of_week')
            ->orderBy('start_time');
    }

    public function note(): HasOne
    {
        return $this->hasOne(CourseNoteModel::class, 'course_id');
    }
}
