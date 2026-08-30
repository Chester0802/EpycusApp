<?php

namespace App\Modules\Calendar\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GradeEvaluationModel extends Model
{
    protected $table = 'grade_evaluations';

    protected $fillable = [
        'course_id',
        'name',
        'weight',
        'obtained_score',
        'max_score',
        'eval_date',
    ];

    protected $casts = [
        'weight' => 'float',
        'obtained_score' => 'float',
        'max_score' => 'float',
        'eval_date' => 'date',
    ];

    /**
     * @return BelongsTo
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(CourseModel::class, 'course_id');
    }
}
