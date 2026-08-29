<?php

declare(strict_types=1);

namespace App\Modules\Motivation\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;

final class UsageTipModel extends Model
{
    protected $table = 'usage_tips';

    protected $fillable = [
        'module_key',
        'content',
    ];
}
