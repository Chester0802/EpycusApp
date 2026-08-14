<?php

declare(strict_types=1);

namespace App\Modules\Calendar\Infrastructure\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $note_id
 * @property int $user_id
 * @property string $filename
 * @property string $original_name
 * @property string $mime_type
 * @property string $extension
 * @property int $size
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read CourseNoteModel $note
 */
final class NoteImageModel extends Model
{
    protected $table = 'note_images';

    protected $fillable = [
        'note_id',
        'user_id',
        'filename',
        'original_name',
        'mime_type',
        'extension',
        'size',
    ];

    protected $casts = [
        'size' => 'integer',
    ];

    public function note(): BelongsTo
    {
        return $this->belongsTo(CourseNoteModel::class, 'note_id');
    }
}
