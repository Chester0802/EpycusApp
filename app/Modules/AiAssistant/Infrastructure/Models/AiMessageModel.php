<?php

declare(strict_types=1);

namespace App\Modules\AiAssistant\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class AiMessageModel extends Model
{
    protected $table = 'ai_messages';

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(AiConversationModel::class, 'conversation_id');
    }

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
