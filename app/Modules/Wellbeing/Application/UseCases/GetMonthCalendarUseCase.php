<?php

declare(strict_types=1);

namespace App\Modules\Wellbeing\Application\UseCases;

use App\Modules\Wellbeing\Domain\Contracts\WellbeingRepositoryInterface;

final class GetMonthCalendarUseCase
{
    public function __construct(
        private WellbeingRepositoryInterface $repository,
    ) {}

    public function execute(int $userId, int $year, int $month): array
    {
        $entries = $this->repository->getEntriesForUserInMonth($userId, $year, $month);

        $days = [];
        foreach ($entries as $entry) {
            $dateKey = $entry->date->toDateString();
            if (! isset($days[$dateKey])) {
                $days[$dateKey] = [
                    'scores' => [],
                    'total' => 0,
                ];
            }
            $days[$dateKey]['scores'][] = $entry->mood_score;
            $days[$dateKey]['total']++;
        }

        $result = [];
        foreach ($days as $date => $data) {
            $avg = round(array_sum($data['scores']) / count($data['scores']));
            $result[$date] = [
                'avg_score' => (int) $avg,
                'entry_count' => $data['total'],
            ];
        }

        return $result;
    }
}
