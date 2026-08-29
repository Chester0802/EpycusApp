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
}
