<?php

declare(strict_types=1);

namespace App\Modules\Motivation\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;

final class UserTipViewModel extends Model
{
    protected $table = 'user_tip_views';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'tip_id',
        'is_dismissed',
        'created_at',
    ];

    protected $casts = [
        'is_dismissed' => 'boolean',
    ];
}
