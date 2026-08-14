<?php

declare(strict_types=1);

namespace App\Modules\Missions\Domain\Contracts;

use App\Modules\Missions\Infrastructure\Models\MissionModel;
use Illuminate\Support\Collection;

interface MissionRepositoryInterface
{
    public function findByIdAndUser(int $missionId, int $userId): ?MissionModel;

    /** @return Collection<int, MissionModel> */
    public function getActiveForUser(int $userId, string $sortBy = 'default'): Collection;

    /** @return Collection<int, MissionModel> */
    public function getCompletedForUser(int $userId): Collection;

    /** @param array<string, mixed> $data */
    public function create(array $data): MissionModel;

    /** @param array<string, mixed> $data */
    public function update(MissionModel $mission, array $data): MissionModel;

    public function delete(MissionModel $mission): bool;

    public function countCompletedToday(int $userId): int;
}
