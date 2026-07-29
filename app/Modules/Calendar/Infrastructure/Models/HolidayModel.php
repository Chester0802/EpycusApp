<?php

declare(strict_types=1);

namespace App\Modules\Calendar\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;

final class HolidayModel extends Model
{
    protected $table = 'holidays';

    protected $fillable = [
        'date', 'name', 'type',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date:Y-m-d',
        ];
    }
}
