<?php

declare(strict_types=1);

namespace App\Modules\Wellbeing\Application\UseCases;

use App\Modules\Wellbeing\Domain\Contracts\WellbeingRepositoryInterface;

final class GetDayDetailUseCase
{
    public function __construct(
        private WellbeingRepositoryInterface $repository,
    ) {}

    public function execute(int $userId, string $date): array
    {
        $entries = $this->repository->getEntriesForUserOnDate($userId, $date);

        return $entries->map(fn ($e) => [
            'id' => $e->id,
            'mood_score' => $e->mood_score,
            'content' => $e->content,
            'tags' => $e->tags ?? [],
            'created_at' => $e->created_at->toIso8601String(),
        ])->toArray();
    }
}
