<?php

declare(strict_types=1);

namespace App\Modules\Skills\Infrastructure\Models;

use App\Modules\Identity\Infrastructure\Models\UserModel;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property string $category
 * @property string|null $icon
 * @property string|null $description
 * @property int $current_level
 * @property int $current_xp
 * @property int $target_xp
 * @property int $total_minutes_practiced
 * @property bool $is_active
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Collection<int, SkillLogModel> $logs
 */
final class SkillModel extends Model
{
    protected $table = 'personal_skills';

    protected $fillable = [
        'user_id',
        'name',
        'category',
        'icon',
        'description',
        'current_level',
        'current_xp',
        'target_xp',
        'total_minutes_practiced',
        'is_active',
    ];

    protected $casts = [
        'current_level' => 'integer',
        'current_xp' => 'integer',
        'target_xp' => 'integer',
        'total_minutes_practiced' => 'integer',
        'is_active' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(UserModel::class, 'user_id');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(SkillLogModel::class, 'skill_id');
    }

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function addPractice(int $minutes, ?string $notes = null): SkillLogModel
    {
        $xpEarned = (int) round($minutes * 1.5); // 1.5 XP por minuto de práctica deliberada

        $this->total_minutes_practiced += $minutes;
        $this->current_xp += $xpEarned;

        // Subir de nivel si supera target_xp
        while ($this->current_xp >= $this->target_xp) {
            $this->current_xp -= $this->target_xp;
            $this->current_level++;
            $this->target_xp = (int) round($this->target_xp * 1.3); // Escalado suave de nivel
        }

        $this->save();

        return $this->logs()->create([
            'user_id' => $this->user_id,
            'duration_minutes' => $minutes,
            'notes' => $notes,
            'xp_earned' => $xpEarned,
            'logged_at' => Carbon::now('America/Lima')->toDateString(),
        ]);
    }
}
