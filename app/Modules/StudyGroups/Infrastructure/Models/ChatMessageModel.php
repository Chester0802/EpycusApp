<?php

declare(strict_types=1);

namespace App\Modules\StudyGroups\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $session_id
 * @property int $user_id
 * @property string $body
 * @property Carbon|null $created_at
 */
final class ChatMessageModel extends Model
{
    protected $table = 'chat_messages';

    public const UPDATED_AT = null;

    protected $fillable = [
        'session_id',
        'user_id',
        'body',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];
}
