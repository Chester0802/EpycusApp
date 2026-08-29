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
 * @property string $type
 * @property float $amount
 * @property string $category
 * @property CarbonInterface $date
 * @property string|null $notes
 */
final class FinanceTransactionModel extends Model
{
    protected $table = 'finance_transactions';

    protected $fillable = [
        'user_id',
        'type',
        'amount',
        'category',
        'date',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'amount' => 'float',
            'date' => 'date:Y-m-d',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(UserModel::class, 'user_id');
    }
}
