<?php

declare(strict_types=1);

namespace App\Shared\Domain\Contracts;

interface CalendarReaderInterface
{
    public function isHoliday(\DateTimeImmutable $date): bool;

    public function isNonWorkingDay(\DateTimeImmutable $date): bool;

    public function isExamWeek(\DateTimeImmutable $date): bool;

    public function getHolidayName(\DateTimeImmutable $date): ?string;

    public function interventionDayFor(\DateTimeImmutable $date): ?int;
}
