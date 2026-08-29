<?php

declare(strict_types=1);

namespace App\Modules\Villains\Infrastructure\Repositories;

use App\Modules\Villains\Domain\Contracts\VillainRepositoryInterface;
use App\Modules\Villains\Infrastructure\Models\VillainInstanceModel;
use App\Modules\Villains\Infrastructure\Models\VillainModel;
use Illuminate\Support\Collection;

final class EloquentVillainRepository implements VillainRepositoryInterface
{
    private const INTERVENTION_START = '2026-09-07';

    private const INTERVENTION_END = '2026-11-11';

    public function findVillainById(int $id): ?VillainModel
    {
        return VillainModel::find($id);
    }

    public function findVillainByCode(string $code): ?VillainModel
    {
        return VillainModel::where('code', $code)->first();
    }

    /** @return Collection<int, VillainModel> */
    public function getAllVillains(): Collection
    {
        return VillainModel::all();
    }

    public function findActiveInstance(int $userId): ?VillainInstanceModel
    {
        return VillainInstanceModel::where('user_id', $userId)
            ->where('status', 'active')
            ->with('villain')
            ->latest('assigned_at')
            ->first();
    }

    public function findInstanceByUserAndWeek(int $userId, int $weekNumber): ?VillainInstanceModel
    {
        return VillainInstanceModel::where('user_id', $userId)
            ->where('week_number', $weekNumber)
            ->first();
    }

    /** @param array<string, mixed> $data */
    public function createInstance(array $data): VillainInstanceModel
    {
        return VillainInstanceModel::create($data);
    }

    /** @param array<string, mixed> $data */
    public function updateInstance(VillainInstanceModel $instance, array $data): VillainInstanceModel
    {
        $instance->update($data);

        return $instance->fresh();
    }

    /** @return Collection<int, VillainInstanceModel> */
    public function getActiveInstances(): Collection
    {
        return VillainInstanceModel::where('status', 'active')
            ->with('villain')
            ->get();
    }

    /** @return Collection<int, VillainInstanceModel> */
    public function getExpiredActiveInstances(\DateTimeImmutable $now): Collection
    {
        return VillainInstanceModel::where('status', 'active')
            ->where('expires_at', '<', $now->format('Y-m-d H:i:s'))
            ->with('villain')
            ->get();
    }

    /**
     * Calcula la semana de intervención (1-10) para una fecha dada.
     * Retorna null si la fecha está fuera del período de intervención.
     */
    public function getInterventionWeekFor(\DateTimeImmutable $date): ?int
    {
        $tz = new \DateTimeZone('America/Lima');
        $start = new \DateTimeImmutable(self::INTERVENTION_START.' 00:00:00', $tz);
        $end = new \DateTimeImmutable(self::INTERVENTION_END.' 23:59:59', $tz);

        if ($date < $start || $date > $end) {
            return null;
        }

        $daysSinceStart = (int) $start->diff($date)->days;

        return (int) floor($daysSinceStart / 7) + 1;
    }

    /**
     * Retorna el DateTimeImmutable del lunes 00:00 Lima de la semana
     * de intervención indicada.
     */
    public function getMondayForWeek(int $weekNumber): \DateTimeImmutable
    {
        $tz = new \DateTimeZone('America/Lima');
        $start = new \DateTimeImmutable(self::INTERVENTION_START.' 00:00:00', $tz);

        return $start->modify('+'.(($weekNumber - 1) * 7).' days');
    }

    /**
     * Retorna el DateTimeImmutable del domingo 23:59 Lima de la semana
     * de intervención indicada.
     */
    public function getSundayForWeek(int $weekNumber): \DateTimeImmutable
    {
        $monday = $this->getMondayForWeek($weekNumber);

        return $monday->modify('+6 days 23 hours 59 minutes');
    }

    public function getWeekNumberForUser(int $userId): int
    {
        $now = new \DateTimeImmutable('now', new \DateTimeZone('America/Lima'));

        return $this->getInterventionWeekFor($now) ?? 0;
    }
}
