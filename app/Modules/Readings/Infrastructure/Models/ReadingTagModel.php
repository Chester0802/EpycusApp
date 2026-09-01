<?php

declare(strict_types=1);

namespace App\Modules\Readings\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $reading_id
 * @property string $tag
 */
final class ReadingTagModel extends Model
{
    protected $table = 'reading_tags';

    public $timestamps = false;

    public $incrementing = false;

    protected $primaryKey = ['reading_id', 'tag'];

    protected $fillable = [
        'reading_id',
        'tag',
    ];

    public function reading(): BelongsTo
    {
        return $this->belongsTo(ReadingModel::class, 'reading_id');
    }
}
