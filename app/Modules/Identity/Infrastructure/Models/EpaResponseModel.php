<?php

declare(strict_types=1);

namespace App\Modules\Identity\Infrastructure\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property string $participant_code
 * @property string $phase
 * @property int $item_2
 * @property int $item_5
 * @property int $item_7
 * @property int $item_10
 * @property int $item_11
 * @property int $item_12
 * @property int $item_13
 * @property int $item_14
 * @property int $total_score
 * @property Carbon $completed_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
final class EpaResponseModel extends Model
{
    use HasFactory;

    protected $table = 'epa_responses';

    protected $fillable = [
        'user_id',
        'participant_code',
        'phase',
        'item_2',
        'item_5',
        'item_7',
        'item_10',
        'item_11',
        'item_12',
        'item_13',
        'item_14',
        'total_score',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'item_2' => 'integer',
            'item_5' => 'integer',
            'item_7' => 'integer',
            'item_10' => 'integer',
            'item_11' => 'integer',
            'item_12' => 'integer',
            'item_13' => 'integer',
            'item_14' => 'integer',
            'total_score' => 'integer',
            'completed_at' => 'datetime',
        ];
    }
}
