<?php

declare(strict_types=1);

namespace App\Modules\Fitness\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string $category
 * @property string $difficulty
 * @property string $target_muscles
 * @property string $instructions
 * @property int $default_duration_seconds
 * @property string $icon
 */
final class FitnessExerciseModel extends Model
{
    protected $table = 'fitness_exercises';

    protected $fillable = [
        'name',
        'slug',
        'category',
        'difficulty',
        'target_muscles',
        'instructions',
        'default_duration_seconds',
        'icon',
    ];

    protected function casts(): array
    {
        return [
            'default_duration_seconds' => 'integer',
        ];
    }
}
