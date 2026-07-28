<?php

declare(strict_types=1);

namespace App\Shared\Domain\Contracts;

interface TelemetryReaderInterface
{
    public function countEventsFor(int $userId, string $eventName, \DateTimeImmutable $from, \DateTimeImmutable $to): int;

    public function getDailyAggregates(\DateTimeImmutable $from, \DateTimeImmutable $to): array;

    public function getActiveUsersOn(\DateTimeImmutable $date): int;
}
