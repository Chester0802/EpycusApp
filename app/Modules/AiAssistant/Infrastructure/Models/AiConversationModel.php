<?php

declare(strict_types=1);

namespace App\Modules\AiAssistant\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class AiConversationModel extends Model
{
    protected $table = 'ai_conversations';

    protected $fillable = [
        'user_id',
        'title',
    ];

    public function messages(): HasMany
    {
        return $this->hasMany(AiMessageModel::class, 'conversation_id')->orderBy('id', 'asc');
    }
}
