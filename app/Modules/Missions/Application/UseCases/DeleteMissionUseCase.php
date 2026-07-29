<?php

declare(strict_types=1);

namespace App\Modules\Missions\Application\UseCases;

use App\Modules\Missions\Domain\Contracts\MissionRepositoryInterface;

final class DeleteMissionUseCase
{
    public function __construct(
        private MissionRepositoryInterface $repository,
    ) {}

    public function execute(int $missionId, int $userId): void
    {
        $mission = $this->repository->findByIdAndUser($missionId, $userId);

        if ($mission) {
            $this->repository->delete($mission);
        }
    }
}
