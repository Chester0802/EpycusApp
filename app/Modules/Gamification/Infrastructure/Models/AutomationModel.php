<?php

declare(strict_types=1);

namespace App\Modules\Gamification\Infrastructure\Models;

use App\Modules\Identity\Infrastructure\Models\UserModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property string $trigger_event
 * @property string $action_type
 * @property array|null $config
 * @property bool $is_active
 */
final class AutomationModel extends Model
{
    protected $table = 'automations';

    protected $fillable = [
        'user_id',
        'name',
        'trigger_event',
        'action_type',
        'config',
        'is_active',
    ];

    protected $casts = [
        'config' => 'array',
        'is_active' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(UserModel::class, 'user_id');
    }

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
