<?php

declare(strict_types=1);

namespace App\Modules\Villains\Application\UseCases;

use App\Modules\Villains\Domain\Contracts\VillainRepositoryInterface;
use App\Modules\Villains\Domain\ValueObjects\VillainCode;
use Carbon\CarbonImmutable;

final class GetCurrentVillainUseCase
{
    public function __construct(
        private VillainRepositoryInterface $repository,
    ) {}

    /**
     * @return array<string, mixed>|null
     */
    public function execute(int $userId): ?array
    {
        $now = CarbonImmutable::now('America/Lima');
        $instance = $this->repository->findActiveInstance($userId);

        if ($instance !== null && $instance->expires_at < $now->format('Y-m-d H:i:s')) {
            $this->repository->updateInstance($instance, ['status' => 'survived']);
            $instance = null;
        }

        if ($instance === null) {
            $weekNumber = $this->repository->getWeekNumberForUser($userId);
            $weekNumToUse = $weekNumber > 0 ? $weekNumber : (int) $now->format('W');

            $existing = $this->repository->findInstanceByUserAndWeek($userId, $weekNumToUse);
            if ($existing !== null) {
                $instance = $existing;
                $instance->load('villain');
            } else {
                $codes = VillainCode::all();
                $codeIndex = abs($weekNumToUse - 1) % count($codes);
                $code = $codes[$codeIndex];

                $villain = $this->repository->findVillainByCode($code) ?? $this->repository->getAllVillains()->first();

                if ($villain !== null) {
                    $monday = $now->startOfWeek();
                    $sunday = $now->endOfWeek();

                    $instance = $this->repository->createInstance([
                        'user_id' => $userId,
                        'villain_id' => $villain->id,
                        'week_number' => $weekNumToUse,
                        'total_hp' => 100,
                        'remaining_hp' => 100,
                        'status' => 'active',
                        'assigned_at' => $monday->format('Y-m-d H:i:s'),
                        'expires_at' => $sunday->format('Y-m-d H:i:s'),
                    ]);
                    $instance->load('villain');
                }
            }
        }

        if ($instance === null) {
            return null;
        }

        $villain = $instance->villain;
        $code = VillainCode::from($villain->code);

        return [
            'id' => $instance->id,
            'villain_id' => $villain->id,
            'code' => $villain->code,
            'name' => $villain->name,
            'description' => $villain->description,
            'weakness_description' => $villain->weakness_description,
            'image_url' => asset($code->imagePath()),
            'week_number' => $instance->week_number,
            'total_hp' => $instance->total_hp,
            'remaining_hp' => $instance->remaining_hp,
            'status' => $instance->status,
            'assigned_at' => $instance->assigned_at,
            'expires_at' => $instance->expires_at,
            'defeated_at' => $instance->defeated_at,
        ];
    }
}
