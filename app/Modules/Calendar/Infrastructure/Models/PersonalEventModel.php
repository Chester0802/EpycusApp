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
 * @property string $title
 * @property string|null $description
 * @property string $type
 * @property Carbon $event_date
 * @property string|null $start_time
 * @property string|null $end_time
 * @property bool $is_recurring
 * @property string|null $recurrence_rule
 * @property string $color
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
final class PersonalEventModel extends Model
{
    protected $table = 'personal_events';

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'type',
        'event_date',
        'start_time',
        'end_time',
        'is_recurring',
        'recurrence_rule',
        'color',
    ];

    protected $casts = [
        'event_date' => 'date',
        'is_recurring' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(UserModel::class, 'user_id');
    }

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function scopeInMonth(Builder $query, int $year, int $month): Builder
    {
        $start = Carbon::createFromDate($year, $month, 1, 'America/Lima')->startOfMonth()->toDateString();
        $end = Carbon::createFromDate($year, $month, 1, 'America/Lima')->endOfMonth()->toDateString();

        return $query->whereBetween('event_date', [$start, $end]);
    }
}
