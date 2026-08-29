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
 * @property CarbonInterface $date
 * @property int $glasses_count
 */
final class DailyHydrationLogModel extends Model
{
    protected $table = 'daily_hydration_logs';

    protected $fillable = [
        'user_id',
        'date',
        'glasses_count',
    ];

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'date' => 'date:Y-m-d',
            'glasses_count' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(UserModel::class, 'user_id');
    }
}
