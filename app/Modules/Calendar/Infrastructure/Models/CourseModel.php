<?php

declare(strict_types=1);

namespace App\Modules\Calendar\Infrastructure\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property string $color
 * @property int|null $period_id
 * @property string|null $professor
 * @property int|null $credits
 * @property float|null $target_grade
 * @property float|null $min_pass_grade
 * @property string|null $syllabus_path
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, CourseSessionModel> $sessions
 * @property-read CourseNoteModel|null $note
 * @property-read AcademicPeriodModel|null $period
 * @property-read \Illuminate\Database\Eloquent\Collection<int, CourseProjectModel> $projects
 */
final class CourseModel extends Model
{
    protected $table = 'courses';

    protected $fillable = [
        'user_id',
        'name',
        'color',
        'starts_at',
        'ends_at',
        'period_id',
        'professor',
        'credits',
        'target_grade',
        'min_pass_grade',
        'syllabus_path',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'date',
            'ends_at' => 'date',
            'target_grade' => 'float',
            'min_pass_grade' => 'float',
            'credits' => 'integer',
        ];
    }

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

    public function period(): BelongsTo
    {
        return $this->belongsTo(AcademicPeriodModel::class, 'period_id');
    }

    /**
     * @return HasMany
     */
    public function projects(): HasMany
    {
        return $this->hasMany(CourseProjectModel::class, 'course_id');
    }

    /**
     * @return HasMany
     */
    public function gradeEvaluations(): HasMany
    {
        return $this->hasMany(GradeEvaluationModel::class, 'course_id')->orderBy('eval_date');
    }
}
