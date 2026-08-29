<?php

declare(strict_types=1);

namespace App\Modules\Pomodoro\Infrastructure\Models;

use App\Modules\Missions\Infrastructure\Models\SubtaskModel;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $pomodoro_session_id
 * @property int $subtask_id
 * @property Carbon $completed_at
 * @property-read SubtaskModel|null $subtask
 * @property-read PomodoroSessionModel|null $session
 */
final class PomodoroSessionSubtaskModel extends Model
{
    protected $table = 'pomodoro_session_subtask';

    public $timestamps = true;

    protected $fillable = [
        'pomodoro_session_id',
        'subtask_id',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'completed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<PomodoroSessionModel, $this>
     */
    public function session(): BelongsTo
    {
        return $this->belongsTo(PomodoroSessionModel::class, 'pomodoro_session_id');
    }

    /**
     * @return BelongsTo<SubtaskModel, $this>
     */
    public function subtask(): BelongsTo
    {
        return $this->belongsTo(SubtaskModel::class, 'subtask_id');
    }
}
