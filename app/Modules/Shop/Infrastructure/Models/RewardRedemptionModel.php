<?php

declare(strict_types=1);

namespace App\Modules\Shop\Infrastructure\Models;

use App\Modules\Identity\Infrastructure\Models\UserModel;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property int|null $reward_id
 * @property string $title
 * @property int $cost_coins
 * @property string $icon
 * @property string $status
 * @property CarbonInterface $redeemed_at
 * @property CarbonInterface|null $used_at
 */
final class RewardRedemptionModel extends Model
{
    protected $table = 'reward_redemptions';

    protected $fillable = [
        'user_id',
        'reward_id',
        'reward_type',
        'title',
        'entertainment_title',
        'entertainment_category',
        'cost_coins',
        'icon',
        'status',
        'review_text',
        'rating',
        'redeemed_at',
        'used_at',
    ];

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'reward_id' => 'integer',
            'cost_coins' => 'integer',
            'rating' => 'integer',
            'redeemed_at' => 'datetime',
            'used_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(UserModel::class, 'user_id');
    }

    public function reward(): BelongsTo
    {
        return $this->belongsTo(CustomRewardModel::class, 'reward_id');
    }
}
