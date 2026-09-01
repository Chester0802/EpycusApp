<?php

declare(strict_types=1);

namespace App\Modules\Readings\Infrastructure\Models;

use App\Modules\Identity\Infrastructure\Models\UserModel;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $user_id
 * @property string $title
 * @property string|null $author
 * @property int|null $year
 * @property string $type
 * @property int|null $total_pages
 * @property string|null $isbn
 * @property string|null $cover_url
 * @property string $status
 * @property int $current_page
 * @property int|null $rating
 * @property int|null $linked_habit_id
 * @property Carbon|null $started_at
 * @property Carbon|null $finished_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Collection<int, ReadingTagModel> $tags
 */
final class ReadingModel extends Model
{
    protected $table = 'readings';

    protected $fillable = [
        'user_id',
        'title',
        'author',
        'year',
        'type',
        'total_pages',
        'isbn',
        'cover_url',
        'status',
        'current_page',
        'rating',
        'linked_habit_id',
        'started_at',
        'finished_at',
    ];

    protected $casts = [
        'year' => 'integer',
        'total_pages' => 'integer',
        'current_page' => 'integer',
        'rating' => 'integer',
        'started_at' => 'date',
        'finished_at' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(UserModel::class, 'user_id');
    }

    public function tags(): HasMany
    {
        return $this->hasMany(ReadingTagModel::class, 'reading_id');
    }

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }
}
