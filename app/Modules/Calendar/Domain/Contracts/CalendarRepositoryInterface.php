<?php

declare(strict_types=1);

namespace App\Modules\Calendar\Domain\Contracts;

use App\Modules\Calendar\Infrastructure\Models\HolidayModel;
use Illuminate\Support\Collection;

interface CalendarRepositoryInterface
{
    /** @return Collection<int, HolidayModel> */
    public function getHolidaysInMonth(int $year, int $month): Collection;
}
