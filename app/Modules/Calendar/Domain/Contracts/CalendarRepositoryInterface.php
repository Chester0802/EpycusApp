<?php

declare(strict_types=1);

namespace App\Modules\Calendar\Domain\Contracts;

use App\Modules\Calendar\Infrastructure\Models\ClassScheduleModel;
use App\Modules\Calendar\Infrastructure\Models\HolidayModel;
use Illuminate\Support\Collection;

interface CalendarRepositoryInterface
{
    /** @return Collection<int, HolidayModel> */
    public function getHolidaysInMonth(int $year, int $month): Collection;

    /** @return Collection<int, ClassScheduleModel> */
    public function getSchedulesForUser(int $userId): Collection;

    /** @param array<string, mixed> $data */
    public function createSchedule(int $userId, array $data): ClassScheduleModel;

    public function deleteSchedule(int $userId, int $id): bool;
}

