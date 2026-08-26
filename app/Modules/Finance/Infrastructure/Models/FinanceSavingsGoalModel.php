<?php

declare(strict_types=1);

namespace App\Modules\Finance\Infrastructure\Models;

use App\Modules\Identity\Infrastructure\Models\UserModel;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property string $title
 * @property float $target_amount
 * @property float $current_amount
 * @property CarbonInterface|null $target_date
 * @property int $reward_xp
 * @property bool $is_completed
 */
final class FinanceSavingsGoalModel extends Model
{
    protected $table = 'finance_savings_goals';

    protected $fillable = [
        'user_id',
        'title',
        'target_amount',
        'current_amount',
        'target_date',
        'reward_xp',
        'is_completed',
    ];

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'target_amount' => 'float',
            'current_amount' => 'float',
            'target_date' => 'date:Y-m-d',
            'reward_xp' => 'integer',
            'is_completed' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(UserModel::class, 'user_id');
    }
}
