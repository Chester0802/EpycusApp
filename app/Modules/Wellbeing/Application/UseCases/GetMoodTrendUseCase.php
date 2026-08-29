<?php

declare(strict_types=1);

namespace App\Modules\Wellbeing\Application\UseCases;

use App\Modules\Wellbeing\Domain\Contracts\WellbeingRepositoryInterface;
use Carbon\Carbon;

final class GetMoodTrendUseCase
{
    public function __construct(
        private WellbeingRepositoryInterface $repository,
    ) {}

    public function execute(int $userId): array
    {
        $to = Carbon::now();
        $from = (clone $to)->subDays(29);

        $entries = $this->repository->getEntriesForUserInRange(
            $userId,
            $from->toDateString(),
            $to->toDateString(),
        );

        $dailyAvgs = [];
        $dailyEnergy = [];
        $dailyStress = [];
        $totalSleep = 0;
        $sleepDays = 0;
        $activityCount = 0;

        foreach ($entries as $entry) {
            $dateKey = $entry->date->toDateString();
            if (! isset($dailyAvgs[$dateKey])) {
                $dailyAvgs[$dateKey] = [];
                $dailyEnergy[$dateKey] = [];
                $dailyStress[$dateKey] = [];
            }
            $dailyAvgs[$dateKey][] = $entry->mood_score;
            if ($entry->energy !== null) {
                $dailyEnergy[$dateKey][] = $entry->energy;
            }
            if ($entry->stress !== null) {
                $dailyStress[$dateKey][] = $entry->stress;
            }
            if ($entry->sleep_hours !== null) {
                $totalSleep += $entry->sleep_hours;
                $sleepDays++;
            }
            if ($entry->physical_activity !== null) {
                $activityCount++;
            }
        }

        $days = [];
        $current = clone $from;
        $totalScore = 0;
        $totalDays = 0;
        $tagCounts = [];

        while ($current <= $to) {
            $dateKey = $current->toDateString();
            if (isset($dailyAvgs[$dateKey])) {
                $avg = round(array_sum($dailyAvgs[$dateKey]) / count($dailyAvgs[$dateKey]));
                $days[$dateKey] = (int) $avg;
                $totalScore += $avg;
                $totalDays++;
            }
            $current->addDay();
        }

        $avgMood = $totalDays > 0 ? round($totalScore / $totalDays, 1) : 0;

        foreach ($entries as $entry) {
            if ($entry->tags) {
                foreach ($entry->tags as $tag) {
                    $tagCounts[$tag] = ($tagCounts[$tag] ?? 0) + 1;
                }
            }
        }
        arsort($tagCounts);
        $topTags = array_slice(array_keys($tagCounts), 0, 5);

        $firstHalf = array_slice($days, 0, (int) floor(count($days) / 2), true);
        $secondHalf = array_slice($days, (int) floor(count($days) / 2), null, true);
        $firstAvg = count($firstHalf) > 0 ? array_sum($firstHalf) / count($firstHalf) : 0;
        $secondAvg = count($secondHalf) > 0 ? array_sum($secondHalf) / count($secondHalf) : 0;

        $trend = match (true) {
            $secondAvg > $firstAvg + 0.3 => 'subiendo',
            $secondAvg < $firstAvg - 0.3 => 'bajando',
            default => 'estable',
        };

        $allEnergy = [];
        $allStress = [];
        foreach ($dailyEnergy as $scores) {
            $allEnergy[] = round(array_sum($scores) / count($scores));
        }
        foreach ($dailyStress as $scores) {
            $allStress[] = round(array_sum($scores) / count($scores));
        }
        $avgEnergy = count($allEnergy) > 0 ? round(array_sum($allEnergy) / count($allEnergy), 1) : null;
        $avgStress = count($allStress) > 0 ? round(array_sum($allStress) / count($allStress), 1) : null;

        return [
            'days' => $days,
            'avg_mood' => $avgMood,
            'trend' => $trend,
            'days_with_entries' => count($dailyAvgs),
            'top_tags' => $topTags,
            'total_entries' => $entries->count(),
            'avg_energy' => $avgEnergy,
            'avg_stress' => $avgStress,
            'avg_sleep_hours' => $sleepDays > 0 ? round($totalSleep / $sleepDays, 1) : null,
            'days_with_activity' => $activityCount,
        ];
    }
}
