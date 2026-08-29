<?php

declare(strict_types=1);

namespace App\Modules\Finance\Infrastructure\Models;

use App\Modules\Identity\Infrastructure\Models\UserModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property int $month
 * @property int $year
 * @property string $category
 * @property float $monthly_limit
 */
final class FinanceBudgetModel extends Model
{
    protected $table = 'finance_budgets';

    protected $fillable = [
        'user_id',
        'month',
        'year',
        'category',
        'monthly_limit',
    ];

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'month' => 'integer',
            'year' => 'integer',
            'monthly_limit' => 'float',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(UserModel::class, 'user_id');
    }
}
