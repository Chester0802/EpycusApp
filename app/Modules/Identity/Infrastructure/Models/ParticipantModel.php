<?php

declare(strict_types=1);

namespace App\Modules\Identity\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;

final class ParticipantModel extends Model
{
    protected $table = 'participants';

    protected $fillable = [
        'user_id',
        'participant_code',
        'student_code',
        'whatsapp',
        'consent_granted_at',
        'enrolled_at',
        'withdrawn_at',
    ];

    protected function casts(): array
    {
        return [
            'student_code' => 'encrypted',
            'whatsapp' => 'encrypted',
            'consent_granted_at' => 'datetime',
            'enrolled_at' => 'datetime',
            'withdrawn_at' => 'datetime',
        ];
    }
}
