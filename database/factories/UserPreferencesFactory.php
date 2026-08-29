<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Modules\Identity\Infrastructure\Models\UserModel;
use App\Modules\Identity\Infrastructure\Models\UserPreferencesModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserPreferencesModel>
 */
final class UserPreferencesFactory extends Factory
{
    protected $model = UserPreferencesModel::class;

    public function definition(): array
    {
        return [
            'user_id' => UserModel::factory(),
            'surface_mode' => 'neumorphism',
            'notifications_enabled' => false,
        ];
    }
}
