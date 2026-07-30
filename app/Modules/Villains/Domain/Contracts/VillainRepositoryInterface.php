<?php

declare(strict_types=1);

namespace App\Modules\Villains\Domain\Contracts;

use App\Modules\Villains\Infrastructure\Models\VillainInstanceModel;
use App\Modules\Villains\Infrastructure\Models\VillainModel;
use Illuminate\Support\Collection;

interface VillainRepositoryInterface
{
    public function findVillainById(int $id): ?VillainModel;

    public function findVillainByCode(string $code): ?VillainModel;

    /** @return Collection<int, VillainModel> */
    public function getAllVillains(): Collection;

    public function findActiveInstance(int $userId): ?VillainInstanceModel;

    public function findInstanceByUserAndWeek(int $userId, int $weekNumber): ?VillainInstanceModel;

    /** @param array<string, mixed> $data */
    public function createInstance(array $data): VillainInstanceModel;

    /** @param array<string, mixed> $data */
    public function updateInstance(VillainInstanceModel $instance, array $data): VillainInstanceModel;

    /** @return Collection<int, VillainInstanceModel> */
    public function getActiveInstances(): Collection;

    /** @return Collection<int, VillainInstanceModel> */
    public function getExpiredActiveInstances(\DateTimeImmutable $now): Collection;

    public function getWeekNumberForUser(int $userId): int;

    public function getInterventionWeekFor(\DateTimeImmutable $date): ?int;

    public function getMondayForWeek(int $weekNumber): \DateTimeImmutable;

    public function getSundayForWeek(int $weekNumber): \DateTimeImmutable;
}
