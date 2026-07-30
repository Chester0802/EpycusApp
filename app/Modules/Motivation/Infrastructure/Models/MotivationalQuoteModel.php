<?php

declare(strict_types=1);

namespace App\Modules\Motivation\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;

final class MotivationalQuoteModel extends Model
{
    protected $table = 'motivational_quotes';

    protected $fillable = [
        'text',
        'author',
        'is_verified',
    ];

    protected $casts = [
        'is_verified' => 'boolean',
    ];
}
