<?php

declare(strict_types=1);

namespace App\Modules\Calendar\Infrastructure\Models;

use App\Modules\Identity\Infrastructure\Models\UserModel;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property int|null $course_id
 * @property int|null $reading_id
 * @property int|null $skill_id
 * @property string|null $node_id
 * @property string $source
 * @property string $question
 * @property string $answer
 * @property int $leitner_box
 * @property Carbon|null $next_review_at
 * @property Carbon|null $last_reviewed_at
 * @property int $review_count
 * @property int $success_streak
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
final class FlashcardModel extends Model
{
    protected $table = 'flashcards';

    protected $fillable = [
        'user_id',
        'course_id',
        'reading_id',
        'skill_id',
        'node_id',
        'source',
        'question',
        'answer',
        'leitner_box',
        'next_review_at',
        'last_reviewed_at',
        'review_count',
        'success_streak',
    ];

    protected $casts = [
        'leitner_box' => 'integer',
        'review_count' => 'integer',
        'success_streak' => 'integer',
        'next_review_at' => 'date',
        'last_reviewed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(UserModel::class, 'user_id');
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(CourseModel::class, 'course_id');
    }

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForCourse(Builder $query, int $courseId): Builder
    {
        return $query->where('course_id', $courseId);
    }

    public function scopeDueForReview(Builder $query, ?string $date = null): Builder
    {
        $targetDate = $date ?? Carbon::now('America/Lima')->toDateString();
        return $query->where(function (Builder $q) use ($targetDate) {
            $q->whereNull('next_review_at')
              ->orWhere('next_review_at', '<=', $targetDate);
        });
    }

    /**
     * Aplica el algoritmo de Repaso Espaciado de Cajas de Leitner.
     *
     * @param 'easy'|'good'|'hard'|'fail' $rating
     */
    public function applyLeitnerReview(string $rating): void
    {
        $now = Carbon::now('America/Lima');
        $currentBox = $this->leitner_box ?: 1;

        if (in_array($rating, ['easy', 'good'], true)) {
            $newBox = min(5, $currentBox + 1);
            $this->success_streak++;
        } elseif ($rating === 'hard') {
            $newBox = $currentBox;
            $this->success_streak = max(0, $this->success_streak);
        } else { // fail
            $newBox = match ($currentBox) {
                1, 2 => 1,
                3, 4 => 2,
                5    => 3,
                default => 1,
            };
            $this->success_streak = 0;
        }

        $intervalDays = match ($newBox) {
            1 => 1,
            2 => 3,
            3 => 7,
            4 => 14,
            5 => 30,
            default => 1,
        };

        // Si fue 'hard', la revisamos mañana sin cambiar de caja
        if ($rating === 'hard') {
            $intervalDays = 1;
        }

        $this->leitner_box = $newBox;
        $this->last_reviewed_at = $now;
        $this->next_review_at = (clone $now)->addDays($intervalDays)->toDateString();
        $this->review_count++;
        $this->save();
    }
}
