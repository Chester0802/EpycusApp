<?php

declare(strict_types=1);

namespace App\Modules\Villains\Infrastructure\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $code
 * @property string $name
 * @property string|null $description
 * @property string|null $weakness_description
 * @property-read Collection<int, VillainInstanceModel> $instances
 */
final class VillainModel extends Model
{
    protected $table = 'villains';

    protected $fillable = [
        'code',
        'name',
        'description',
        'weakness_description',
    ];

    /** @return HasMany<VillainInstanceModel, $this> */
    public function instances(): HasMany
    {
        return $this->hasMany(VillainInstanceModel::class, 'villain_id');
    }
}
