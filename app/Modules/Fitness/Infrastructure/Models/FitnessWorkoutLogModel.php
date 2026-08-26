<?php

declare(strict_types=1);

namespace App\Modules\Fitness\Infrastructure\Models;

use App\Modules\Identity\Infrastructure\Models\UserModel;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property string $routine_name
 * @property int $duration_minutes
 * @property int $calories_burned
 * @property string|null $notes
 * @property CarbonInterface $performed_at
 */
final class FitnessWorkoutLogModel extends Model
{
    protected $table = 'fitness_workout_logs';

    protected $fillable = [
        'user_id',
        'routine_name',
        'duration_minutes',
        'calories_burned',
        'notes',
        'performed_at',
    ];

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'duration_minutes' => 'integer',
            'calories_burned' => 'integer',
            'performed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(UserModel::class, 'user_id');
    }
}
