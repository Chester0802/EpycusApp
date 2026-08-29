<?php

declare(strict_types=1);

namespace App\Modules\Motivation\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;

final class UserQuoteViewModel extends Model
{
    protected $table = 'user_quote_views';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'quote_id',
        'created_at',
    ];
}
