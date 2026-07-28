<?php

declare(strict_types=1);

namespace App\Modules\Identity\Infrastructure\Models;

use App\Modules\Habits\Infrastructure\Models\HabitModel;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * @property string $alias
 * @property string $role
 * @property string|null $career
 * @property string|null $avatar_style
 * @property string|null $avatar_gender
 * @property int|null $cycle
 * @property string|null $institution_type
 */
final class UserModel extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $table = 'users';

    protected $fillable = [
        'name',
        'email',
        'password',
        'alias',
        'role',
        'career',
        'avatar_style',
        'avatar_gender',
        'cycle',
        'institution_type',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'cycle' => 'integer',
        ];
    }

    /**
     * @return HasMany<HabitModel, $this>
     */
    public function habits(): HasMany
    {
        return $this->hasMany(HabitModel::class, 'user_id');
    }

    /**
     * UserModel no vive en App\Models, así que la convención de nombres de
     * HasFactory no encuentra la factory sola: hay que indicarla explícitamente.
     */
    protected static function newFactory(): UserFactory
    {
        return UserFactory::new();
    }
}
