<?php

declare(strict_types=1);

namespace App\Modules\AiAssistant\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;

final class AiQuotaModel extends Model
{
    protected $table = 'ai_quotas';

    protected $fillable = [
        'user_id',
        'date',
        'used_count',
    ];

    protected $casts = [
        'date' => 'date:Y-m-d',
        'used_count' => 'integer',
    ];

    public static function recordUsage(int $userId, ?string $feature = null): self
    {
        $today = \Carbon\Carbon::now('America/Lima')->toDateString();
        $record = self::firstOrCreate(
            ['user_id' => $userId, 'date' => $today],
            ['used_count' => 0]
        );
        $record->increment('used_count');

        return $record;
    }
}

