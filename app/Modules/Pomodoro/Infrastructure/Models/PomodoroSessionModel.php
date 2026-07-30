<?php

declare(strict_types=1);

namespace App\Modules\Pomodoro\Infrastructure\Models;

use App\Modules\Pomodoro\Domain\ValueObjects\SessionState;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $user_id
 * @property int|null $mission_id
 * @property int $planned_minutes
 * @property Carbon $started_at
 * @property Carbon|null $paused_at
 * @property int $total_paused_seconds
 * @property Carbon|null $ended_at
 * @property string $status
 * @property int|null $focus_minutes
 */
final class PomodoroSessionModel extends Model
{
    protected $table = 'pomodoro_sessions';

    protected $fillable = [
        'user_id',
        'mission_id',
        'planned_minutes',
        'started_at',
        'paused_at',
        'total_paused_seconds',
        'ended_at',
        'status',
        'focus_minutes',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'paused_at' => 'datetime',
            'ended_at' => 'datetime',
            'total_paused_seconds' => 'integer',
            'planned_minutes' => 'integer',
            'focus_minutes' => 'integer',
        ];
    }

    public function subtaskCompletions(): HasMany
    {
        return $this->hasMany(PomodoroSessionSubtaskModel::class, 'pomodoro_session_id');
    }

    /**
     * Segundos de foco efectivo transcurridos hasta ahora (o hasta que se
     * pausó), descontando todas las pausas ya registradas.
     */
    public function elapsedActiveSeconds(?Carbon $now = null): int
    {
        $referenceEnd = $this->status === SessionState::PAUSED
            ? $this->paused_at
            : ($now ?? Carbon::now());

        // OJO: en Carbon 3, `$a->diffInSeconds($b)` da NEGATIVO cuando $b
        // es anterior a $a (es "desde $a hacia $b", no un valor absoluto
        // por default como en Carbon 2). `started_at` siempre es anterior
        // a `$referenceEnd` en un uso normal, así que el orden correcto es
        // `started_at->diffInSeconds($referenceEnd)` — probado con
        // tinker, no asumido, porque el orden contrario dio 0 en todos los
        // tests de este módulo hasta corregirlo acá.
        // Carbon 3 devuelve float acá (no int como en Carbon 2) — se
        // trunca explícito, no se confía en una coerción implícita.
        $rawSeconds = (int) $this->started_at->diffInSeconds($referenceEnd);

        return max(0, $rawSeconds - $this->total_paused_seconds);
    }
}
