<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Modules\Identity\Infrastructure\Models\UserModel;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<UserModel>
 */
final class UserFactory extends Factory
{
    protected $model = UserModel::class;

    protected static ?string $password;

    public function definition(): array
    {
        return [
            'name' => \fake()->name(),
            'email' => \fake()->unique()->safeEmail(),
            'alias' => \fake()->unique()->userName(),
            'career' => 'Ingeniería de Sistemas',
            'avatar_style' => 'systems',
            'avatar_gender' => 'm',
            'cycle' => 1,
            'institution_type' => 'universidad',
            'email_verified_at' => now(),
            'password' => self::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function participant(): static
    {
        $careers = [
            'Medicina', 'Enfermería', 'Ingeniería Civil', 'Derecho',
            'Administración de Empresas', 'Ingeniería de Sistemas',
        ];

        return $this->state(fn (array $attributes) => [
            'career' => \fake()->randomElement($careers),
            'avatar_style' => \fake()->randomElement(['health', 'business', 'technical', 'systems', 'law']),
            'avatar_gender' => \fake()->randomElement(['m', 'f']),
            'cycle' => \fake()->numberBetween(1, 10),
            'institution_type' => \fake()->randomElement(['universidad', 'instituto']),
        ]);
    }

}
