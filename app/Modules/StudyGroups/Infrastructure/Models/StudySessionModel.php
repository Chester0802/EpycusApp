<?php

declare(strict_types=1);

namespace App\Modules\StudyGroups\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $host_id
 * @property string $name
 * @property int $max_seats
 * @property int $focus_minutes
 * @property int $break_minutes
 * @property int $cycles
 * @property int $current_cycle
 * @property string $phase
 * @property Carbon|null $phase_started_at
 * @property Carbon|null $phase_ends_at
 * @property string $state
 * @property Carbon|null $started_at
 * @property Carbon|null $closed_at
 * @property Carbon|null $created_at
 */
final class StudySessionModel extends Model
{
    protected $table = 'study_sessions';

    protected $fillable = [
        'host_id',
        'name',
        'max_seats',
        'focus_minutes',
        'break_minutes',
        'cycles',
        'current_cycle',
        'phase',
        'phase_started_at',
        'phase_ends_at',
        'state',
        'started_at',
        'closed_at',
    ];

    protected function casts(): array
    {
        return [
            'focus_minutes' => 'integer',
            'break_minutes' => 'integer',
            'cycles' => 'integer',
            'current_cycle' => 'integer',
            'phase_started_at' => 'datetime',
            'phase_ends_at' => 'datetime',
            'started_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    /** @return HasMany<ChatMessageModel, $this> */
    public function messages(): HasMany
    {
        return $this->hasMany(ChatMessageModel::class, 'session_id');
    }
}
