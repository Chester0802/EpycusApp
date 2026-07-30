<?php

declare(strict_types=1);

namespace App\Modules\AiAssistant\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;

final class AiMessageModel extends Model
{
    protected $table = 'ai_messages';

    protected $fillable = [
        'conversation_id',
        'role',
        'content',
        'category',
        'rating',
    ];

    protected $casts = [
        'rating' => 'integer',
    ];
}
