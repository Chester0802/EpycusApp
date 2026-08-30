<?php

declare(strict_types=1);

namespace App\Modules\Calendar\Infrastructure\Models;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $user_id
 * @property string $year
 * @property string $period
 * @property bool $is_current
 * @property Carbon|null $starts_at
 * @property Carbon|null $ends_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
final class AcademicPeriodModel extends Model
{
    protected $table = 'academic_periods';

    protected $fillable = [
        'user_id',
        'year',
        'period',
        'is_current',
        'starts_at',
        'ends_at',
    ];

    protected function casts(): array
    {
        return [
            'is_current' => 'boolean',
            'starts_at' => 'date',
            'ends_at' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function courses(): HasMany
    {
        return $this->hasMany(CourseModel::class, 'period_id');
    }
}
