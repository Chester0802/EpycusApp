<?php

declare(strict_types=1);

namespace App\Modules\Wellbeing\Presentation\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Wellbeing\Application\DTOs\CreateEntryDTO;
use App\Modules\Wellbeing\Application\DTOs\EditEntryDTO;
use App\Modules\Wellbeing\Application\UseCases\CreateEntryUseCase;
use App\Modules\Wellbeing\Application\UseCases\EditEntryUseCase;
use App\Modules\Wellbeing\Application\UseCases\GetDayDetailUseCase;
use App\Modules\Wellbeing\Application\UseCases\GetMonthCalendarUseCase;
use App\Modules\Wellbeing\Application\UseCases\GetMoodTrendUseCase;
use App\Modules\Wellbeing\Domain\ValueObjects\MoodScore;
use App\Shared\Domain\Contracts\CalendarReaderInterface;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

final class WellbeingController extends Controller
{
    public function __construct(
        private GetMonthCalendarUseCase $getMonthCalendar,
        private GetDayDetailUseCase $getDayDetail,
        private CreateEntryUseCase $createEntry,
        private EditEntryUseCase $editEntry,
        private GetMoodTrendUseCase $getMoodTrend,
        private CalendarReaderInterface $calendarReader,
    ) {}

    public function index(Request $request): Response
    {
        $userId = (int) Auth::id();
        $month = (int) $request->query('month', Carbon::now()->month);
        $year = (int) $request->query('year', Carbon::now()->year);

        $days = $this->getMonthCalendar->execute($userId, $year, $month);

        $start = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $end = (clone $start)->endOfMonth();

        $holidays = [];
        $examDates = [];
        $current = clone $start;
        while ($current <= $end) {
            $dateImmutable = CarbonImmutable::createFromDate($current->year, $current->month, $current->day);
            $dateKey = $current->toDateString();
            if ($this->calendarReader->isHoliday($dateImmutable)) {
                $holidays[$dateKey] = [
                    'name' => $this->calendarReader->getHolidayName($dateImmutable),
                ];
            }
            if ($this->calendarReader->isExamWeek($dateImmutable)) {
                $examDates[$dateKey] = true;
            }
            $current->addDay();
        }

        $wellbeingDays = [];
        foreach ($days as $date => $data) {
            $wellbeingDays[$date] = $data;
        }

        $tips = config('wellbeing.health_tips', []);
        $dailyTip = ! empty($tips) ? $tips[array_rand($tips)] : null;

        return Inertia::render('Wellbeing/Index', [
            'month' => $month,
            'year' => $year,
            'todayDate' => Carbon::now()->toDateString(),
            'days' => $wellbeingDays,
            'holidays' => $holidays,
            'examDates' => $examDates,
            'moodScale' => MoodScore::all(),
            'entryTags' => config('wellbeing.tags', []),
            'healthTip' => $dailyTip,
            'physicalActivityTypes' => config('wellbeing.physical_activity_types', []),
        ]);
    }

    public function day(Request $request): Response
    {
        $userId = (int) Auth::id();
        $date = $request->query('date', Carbon::now()->toDateString());

        $entries = $this->getDayDetail->execute($userId, $date);

        $avgScore = 0;
        if (count($entries) > 0) {
            $avgScore = (int) round(array_sum(array_column($entries, 'mood_score')) / count($entries));
        }

        $dateImmutable = CarbonImmutable::createFromFormat('Y-m-d', $date);
        $isHoliday = $dateImmutable ? $this->calendarReader->isHoliday($dateImmutable) : false;
        $holidayName = $dateImmutable ? $this->calendarReader->getHolidayName($dateImmutable) : null;
        $isExam = $dateImmutable ? $this->calendarReader->isExamWeek($dateImmutable) : false;

        return Inertia::render('Wellbeing/Day', [
            'date' => $date,
            'entries' => $entries,
            'avgScore' => $avgScore,
            'moodScale' => MoodScore::all(),
            'entryTags' => config('wellbeing.tags', []),
            'physicalActivityTypes' => config('wellbeing.physical_activity_types', []),
            'isHoliday' => $isHoliday,
            'holidayName' => $holidayName,
            'isExam' => $isExam,
        ]);
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'date' => 'required|date',
            'mood_score' => 'required|integer|min:1|max:5',
            'energy' => 'nullable|integer|min:1|max:5',
            'stress' => 'nullable|integer|min:1|max:5',
            'sleep_hours' => 'nullable|numeric|min:0|max:24',
            'physical_activity' => 'nullable|array',
            'physical_activity.type' => 'required_with:physical_activity|string',
            'physical_activity.duration' => 'required_with:physical_activity|integer|min:1|max:600',
            'content' => 'nullable|string|max:5000',
            'tags' => 'nullable|array',
            'tags.*' => 'string|in:' . implode(',', config('wellbeing.tags', [])),
        ]);

        $dto = new CreateEntryDTO(
            userId: (int) Auth::id(),
            date: $validated['date'],
            moodScore: (int) $validated['mood_score'],
            energy: isset($validated['energy']) ? (int) $validated['energy'] : null,
            stress: isset($validated['stress']) ? (int) $validated['stress'] : null,
            sleepHours: isset($validated['sleep_hours']) ? (float) $validated['sleep_hours'] : null,
            physicalActivity: $validated['physical_activity'] ?? null,
            content: $validated['content'] ?? null,
            tags: $validated['tags'] ?? [],
        );

        $result = $this->createEntry->execute($dto);

        if ($request->wantsJson()) {
            return response()->json($result);
        }

        $msg = $result['xp_awarded'] > 0
            ? "Entrada guardada. ¡+{$result['xp_awarded']} XP!"
            : 'Entrada guardada.';

        return back()->with('success', $msg);
    }

    public function update(Request $request, int $id): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'mood_score' => 'required|integer|min:1|max:5',
            'energy' => 'nullable|integer|min:1|max:5',
            'stress' => 'nullable|integer|min:1|max:5',
            'sleep_hours' => 'nullable|numeric|min:0|max:24',
            'physical_activity' => 'nullable|array',
            'physical_activity.type' => 'required_with:physical_activity|string',
            'physical_activity.duration' => 'required_with:physical_activity|integer|min:1|max:600',
            'content' => 'nullable|string|max:5000',
            'tags' => 'nullable|array',
            'tags.*' => 'string|in:' . implode(',', config('wellbeing.tags', [])),
        ]);

        $dto = new EditEntryDTO(
            entryId: $id,
            userId: (int) Auth::id(),
            moodScore: (int) $validated['mood_score'],
            energy: isset($validated['energy']) ? (int) $validated['energy'] : null,
            stress: isset($validated['stress']) ? (int) $validated['stress'] : null,
            sleepHours: isset($validated['sleep_hours']) ? (float) $validated['sleep_hours'] : null,
            physicalActivity: $validated['physical_activity'] ?? null,
            content: $validated['content'] ?? null,
            tags: $validated['tags'] ?? [],
        );

        try {
            $result = $this->editEntry->execute($dto);
        } catch (\RuntimeException $e) {
            return back()->with('error', 'No puedes modificar esta entrada.');
        }

        if ($request->wantsJson()) {
            return response()->json($result);
        }

        return back()->with('success', 'Entrada actualizada.');
    }

    public function trend(): JsonResponse
    {
        $userId = (int) Auth::id();
        $data = $this->getMoodTrend->execute($userId);

        return response()->json($data);
    }
}
