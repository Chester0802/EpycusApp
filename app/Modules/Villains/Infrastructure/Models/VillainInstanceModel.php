<?php

declare(strict_types=1);

namespace App\Modules\Villains\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property int $villain_id
 * @property int $week_number
 * @property int $total_hp
 * @property int $remaining_hp
 * @property string $status
 * @property string $assigned_at
 * @property string $expires_at
 * @property string|null $defeated_at
 * @property-read VillainModel $villain
 */
final class VillainInstanceModel extends Model
{
    protected $table = 'villain_instances';

    protected $fillable = [
        'user_id',
        'villain_id',
        'week_number',
        'total_hp',
        'remaining_hp',
        'status',
        'assigned_at',
        'expires_at',
        'defeated_at',
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
        'expires_at' => 'datetime',
        'defeated_at' => 'datetime',
    ];

    /** @return BelongsTo<VillainModel, $this> */
    public function villain(): BelongsTo
    {
        return $this->belongsTo(VillainModel::class, 'villain_id');
    }
}
