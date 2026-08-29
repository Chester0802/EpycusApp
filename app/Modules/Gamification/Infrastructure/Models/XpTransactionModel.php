<?php

declare(strict_types=1);

namespace App\Modules\Gamification\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $user_id
 * @property int $amount
 * @property int $base_amount
 * @property float $multiplier
 * @property string $source_type
 * @property int $source_id
 * @property bool $was_capped
 */
final class XpTransactionModel extends Model
{
    public $timestamps = false;

    protected $table = 'xp_transactions';

    protected $fillable = [
        'user_id',
        'amount',
        'base_amount',
        'multiplier',
        'source_type',
        'source_id',
        'was_capped',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'base_amount' => 'integer',
            'multiplier' => 'float',
            'source_id' => 'integer',
            'was_capped' => 'boolean',
        ];
    }
}
