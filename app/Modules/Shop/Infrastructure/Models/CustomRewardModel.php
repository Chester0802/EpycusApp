<?php

declare(strict_types=1);

namespace App\Modules\Shop\Infrastructure\Models;

use App\Modules\Identity\Infrastructure\Models\UserModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $user_id
 * @property string $title
 * @property int $cost_coins
 * @property string $icon
 * @property string $category
 * @property bool $is_active
 */
final class CustomRewardModel extends Model
{
    protected $table = 'custom_rewards';

    protected $fillable = [
        'user_id',
        'title',
        'cost_coins',
        'icon',
        'category',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'cost_coins' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(UserModel::class, 'user_id');
    }

    public function redemptions(): HasMany
    {
        return $this->hasMany(RewardRedemptionModel::class, 'reward_id');
    }
}
