<?php

declare(strict_types=1);

namespace App\Modules\Calendar\Infrastructure\Models;

use App\Modules\Identity\Infrastructure\Models\UserModel;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property array<string, mixed>|null $nodes
 * @property array<string, mixed>|null $edges
 * @property array<string, mixed>|null $stats
 * @property Carbon|null $last_generated_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read UserModel $user
 */
final class UserKnowledgeGraphModel extends Model
{
    protected $table = 'user_knowledge_graphs';

    protected $fillable = [
        'user_id',
        'nodes',
        'edges',
        'stats',
        'last_generated_at',
    ];

    protected $casts = [
        'nodes' => 'array',
        'edges' => 'array',
        'stats' => 'array',
        'last_generated_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(UserModel::class, 'user_id');
    }
}
