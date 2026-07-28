<?php

declare(strict_types=1);

namespace App\Modules\Gamification\Infrastructure\Repositories;

use App\Modules\Gamification\Domain\Contracts\GamificationRepositoryInterface;
use App\Modules\Gamification\Infrastructure\Models\UserProgressModel;
use App\Modules\Gamification\Infrastructure\Models\XpTransactionModel;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class EloquentGamificationRepository implements GamificationRepositoryInterface
{
    public function findOrCreateProgress(int $userId): UserProgressModel
    {
        // `firstOrCreate` no relee la fila desde la base tras insertarla —
        // los defaults a nivel de columna (migración) existen en la fila
        // real, pero el objeto en memoria queda con esos atributos en
        // `null` a menos que se pasen acá explícitamente. Sin esto,
        // `$progress->current_streak` (etc.) es `null` para un usuario
        // nuevo, no `0` — encontrado por un test real, no asumido.
        return UserProgressModel::query()->firstOrCreate(
            ['user_id' => $userId],
            [
                'total_xp' => 0,
                'current_level' => 1,
                'current_phase' => 1,
                'current_streak' => 0,
                'longest_streak' => 0,
                'grace_days_left' => (int) config('gamification.streak.grace_days_per_month'),
                'coins' => 0,
            ],
        );
    }

    public function countTodaysTransactions(int $userId, string $sourceType, \DateTimeImmutable $today): int
    {
        return XpTransactionModel::query()
            ->where('user_id', $userId)
            ->where('source_type', $sourceType)
            ->whereDate('created_at', $today->format('Y-m-d'))
            ->count();
    }

    public function insertTransactionIfNew(array $data): bool
    {
        // insertOrIgnore respeta uq_idempotency (user_id, source_type,
        // source_id) — 0 filas afectadas significa "ya existía", no un
        // error. Es la pieza entera de idempotencia del módulo
        // (docs/03-GAMIFICACION.md §8): no se envuelve en try/catch porque
        // no es una excepción, es un resultado esperado y normal.
        $affected = DB::table('xp_transactions')->insertOrIgnore($data);

        return $affected > 0;
    }

    public function updateProgress(int $userId, array $data): void
    {
        UserProgressModel::query()->where('user_id', $userId)->update($data);
    }

    public function progressWithPossibleStreakGap(\DateTimeImmutable $yesterday): Collection
    {
        return UserProgressModel::query()
            ->where('current_streak', '>', 0)
            ->where(function ($query) use ($yesterday) {
                $query->whereNull('last_activity_on')
                    ->orWhere('last_activity_on', '<', $yesterday->format('Y-m-d'));
            })
            ->get();
    }
}
