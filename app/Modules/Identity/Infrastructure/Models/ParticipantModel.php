<?php

declare(strict_types=1);

namespace App\Modules\Identity\Infrastructure\Models;

use Database\Factories\ParticipantFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $user_id
 * @property string $participant_code
 * @property string|null $student_code
 * @property string|null $whatsapp
 * @property Carbon|null $consent_granted_at
 * @property Carbon|null $enrolled_at
 * @property Carbon|null $withdrawn_at
 */
final class ParticipantModel extends Model
{
    /** @use HasFactory<ParticipantFactory> */
    use HasFactory;

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

    /**
     * ParticipantModel no vive en App\Models, así que la convención de
     * nombres de HasFactory no encuentra la factory sola.
     */
    protected static function newFactory(): ParticipantFactory
    {
        return ParticipantFactory::new();
    }
}
