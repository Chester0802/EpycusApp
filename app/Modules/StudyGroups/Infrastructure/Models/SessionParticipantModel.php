<?php

declare(strict_types=1);

namespace App\Modules\StudyGroups\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;

final class SessionParticipantModel extends Model
{
    protected $table = 'session_participants';

    public $timestamps = false;

    protected $fillable = [
        'session_id',
        'user_id',
        'joined_at',
        'left_at',
    ];

    protected $casts = [
        'joined_at' => 'datetime',
        'left_at' => 'datetime',
    ];
}
