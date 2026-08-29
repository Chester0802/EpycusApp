<?php

declare(strict_types=1);

namespace App\Modules\Calendar\Infrastructure\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $course_id
 * @property int $user_id
 * @property array<string, mixed>|null $content
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read CourseModel $course
 * @property-read \Illuminate\Database\Eloquent\Collection<int, NoteImageModel> $images
 */
final class CourseNoteModel extends Model
{
    protected $table = 'course_notes';

    protected $fillable = [
        'course_id',
        'user_id',
        'content',
    ];

    protected $casts = [
        'content' => 'array',
    ];

    public function course(): BelongsTo
    {
        return $this->belongsTo(CourseModel::class, 'course_id');
    }

    public function images(): HasMany
    {
        return $this->hasMany(NoteImageModel::class, 'note_id');
    }
}
