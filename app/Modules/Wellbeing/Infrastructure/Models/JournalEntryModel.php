<?php

declare(strict_types=1);

namespace App\Modules\Wellbeing\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;

final class JournalEntryModel extends Model
{
    protected $table = 'journal_entries';

    protected $fillable = [
        'user_id', 'date', 'mood_score', 'energy', 'stress',
        'sleep_hours', 'physical_activity', 'content', 'tags',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date:Y-m-d',
            'mood_score' => 'integer',
            'energy' => 'integer',
            'stress' => 'integer',
            'sleep_hours' => 'float',
            'physical_activity' => 'array',
            'content' => 'encrypted',
            'tags' => 'array',
        ];
    }
}
