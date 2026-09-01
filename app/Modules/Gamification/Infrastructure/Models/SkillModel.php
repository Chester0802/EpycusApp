<?php

declare(strict_types=1);

namespace App\Modules\Gamification\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;

class SkillModel extends Model
{
    protected $table = 'skills';
    protected $fillable = ['name', 'key', 'icon', 'color', 'description'];
}
