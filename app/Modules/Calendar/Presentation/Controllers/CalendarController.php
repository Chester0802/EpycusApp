<?php

declare(strict_types=1);

namespace App\Modules\Calendar\Presentation\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Calendar\Domain\Contracts\CalendarRepositoryInterface;
use App\Modules\Missions\Domain\Contracts\MissionRepositoryInterface;
use App\Shared\Domain\Contracts\CalendarReaderInterface;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

final class CalendarController extends Controller
{
    public function __construct(
        private CalendarRepositoryInterface $calendar,
        private CalendarReaderInterface $calendarReader,
        private MissionRepositoryInterface $missions,
    ) {}

    public function index(Request $request): Response
    {
        $userId = (int) Auth::id();
        $month = (int) $request->query('month', Carbon::now()->month);
        $year = (int) $request->query('year', Carbon::now()->year);

        $start = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $end = (clone $start)->endOfMonth();
        $today = Carbon::now()->toDateString();

        $holidays = $this->calendar->getHolidaysInMonth($year, $month)
            ->keyBy(fn ($h) => $h->date->toDateString())
            ->map(fn ($h) => ['name' => $h->name, 'type' => $h->type])
            ->toArray();

        $activeMissions = $this->missions->getActiveForUser($userId);
        $completedMissions = $this->missions->getCompletedForUser($userId);
        $allMissions = $activeMissions->merge($completedMissions);

        $missionsByDate = [];
        foreach ($allMissions as $m) {
            $date = $m->due_date?->toDateString();
            if ($date && $date >= $start->toDateString() && $date <= $end->toDateString()) {
                $missionsByDate[$date][] = [
                    'id' => $m->id,
                    'title' => $m->title,
                    'difficulty' => $m->difficulty,
                    'is_completed' => (bool) $m->completed_at,
                ];
            }
        }

        $examDates = [];
        $current = clone $start;
        while ($current <= $end) {
            $dateImmutable = CarbonImmutable::createFromDate($current->year, $current->month, $current->day);
            if ($this->calendarReader->isExamWeek($dateImmutable)) {
                $examDates[$current->toDateString()] = true;
            }
            $current->addDay();
        }

        return Inertia::render('Calendar/Index', [
            'month' => $month,
            'year' => $year,
            'todayDate' => $today,
            'holidays' => $holidays,
            'missionsByDate' => $missionsByDate,
            'examDates' => $examDates,
            'academicCycle' => config('academic.current_cycle.name', '2026-2'),
        ]);
    }
}
