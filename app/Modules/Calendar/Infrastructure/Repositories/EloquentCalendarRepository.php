<?php

declare(strict_types=1);

namespace App\Modules\Calendar\Infrastructure\Repositories;

use App\Modules\Calendar\Domain\Contracts\CalendarRepositoryInterface;
use App\Modules\Calendar\Infrastructure\Models\HolidayModel;
use App\Shared\Domain\Contracts\CalendarReaderInterface;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

final class EloquentCalendarRepository implements CalendarRepositoryInterface, CalendarReaderInterface
{
    private const CACHE_KEY = 'calendar_holidays';
    private const CACHE_TTL = 86400;

    public function getHolidaysInMonth(int $year, int $month): Collection
    {
        $all = $this->getAllHolidays();

        return $all->filter(fn ($h) => (int) $h->date->format('m') === $month && (int) $h->date->format('Y') === $year)
            ->values();
    }

    /** @return Collection<int, HolidayModel> */
    private function getAllHolidays(): Collection
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
            return HolidayModel::query()->orderBy('date')->get();
        });
    }

    private function findByDate(string $date): ?HolidayModel
    {
        return $this->getAllHolidays()->first(fn ($h) => $h->date->toDateString() === $date);
    }

    public function isHoliday(\DateTimeImmutable $date): bool
    {
        return $this->findByDate($date->format('Y-m-d')) !== null;
    }

    public function isNonWorkingDay(\DateTimeImmutable $date): bool
    {
        return $this->isHoliday($date);
    }

    public function isExamWeek(\DateTimeImmutable $date): bool
    {
        $examWeeks = config('academic.current_cycle.exam_weeks', []);
        $check = Carbon::instance($date);

        foreach ($examWeeks as $week) {
            $from = Carbon::parse($week['from']);
            $to = Carbon::parse($week['to']);

            if ($check->between($from, $to)) {
                return true;
            }
        }

        return false;
    }

    public function getHolidayName(\DateTimeImmutable $date): ?string
    {
        return $this->findByDate($date->format('Y-m-d'))?->name;
    }

    public function interventionDayFor(\DateTimeImmutable $date): ?int
    {
        $start = Carbon::parse(config('academic.current_cycle.starts_on', '2026-09-07'));
        $check = Carbon::instance($date);

        if ($check < $start) {
            return null;
        }

        return (int) $start->startOfDay()->diffInDays($check->startOfDay()) + 1;
    }
}
