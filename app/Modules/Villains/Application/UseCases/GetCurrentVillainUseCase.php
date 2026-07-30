<?php

declare(strict_types=1);

namespace App\Modules\Villains\Application\UseCases;

use App\Modules\Villains\Domain\Contracts\VillainRepositoryInterface;
use App\Modules\Villains\Domain\ValueObjects\VillainCode;

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
        $instance = $this->repository->findActiveInstance($userId);
        if ($instance === null && (app()->environment('local') || config('app.debug'))) {
            $villain = $this->repository->findVillainByCode('procrastination');
            if ($villain !== null) {
                $now = new \DateTimeImmutable('now', new \DateTimeZone('America/Lima'));
                $instance = $this->repository->createInstance([
                    'user_id' => $userId,
                    'villain_id' => $villain->id,
                    'week_number' => 1,
                    'total_hp' => 100,
                    'remaining_hp' => 100,
                    'status' => 'active',
                    'assigned_at' => $now->modify('-1 day')->format('Y-m-d H:i:s'),
                    'expires_at' => $now->modify('+6 days')->format('Y-m-d H:i:s'),
                ]);
                $instance->load('villain');
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
