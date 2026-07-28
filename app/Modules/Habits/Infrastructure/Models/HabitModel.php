<?php

declare(strict_types=1);

namespace App\Modules\Habits\Infrastructure\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property string $title
 * @property string $category
 * @property array<string, mixed> $frequency
 * @property string|null $icon
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Collection<int, HabitCompletionModel> $completions
 */
final class HabitModel extends Model
{
    use SoftDeletes;

    protected $table = 'habits';

    protected $fillable = [
        'user_id',
        'title',
        'category',
        'frequency',
        'icon',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'frequency' => 'array',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return HasMany<HabitCompletionModel, $this>
     */
    public function completions(): HasMany
    {
        return $this->hasMany(HabitCompletionModel::class, 'habit_id');
    }
}
