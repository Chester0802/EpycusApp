<?php

declare(strict_types=1);

namespace App\Modules\Habits\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $habit_id
 * @property int $user_id
 * @property string $completed_for
 * @property string $completed_at
 * @property bool $is_late
 * @property Carbon|null $created_at
 * @property-read HabitModel $habit
 */
final class HabitCompletionModel extends Model
{
    public $timestamps = false;

    protected $table = 'habit_completions';

    protected $fillable = [
        'habit_id',
        'user_id',
        'completed_for',
        'completed_at',
        'is_late',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'is_late' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<HabitModel, $this>
     */
    public function habit(): BelongsTo
    {
        return $this->belongsTo(HabitModel::class, 'habit_id');
    }
}
