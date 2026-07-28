<?php

declare(strict_types=1);

namespace App\Shared\Domain\Contracts;

interface UserProgressReaderInterface
{
    public function getLevelFor(int $userId): int;

    public function getPhaseFor(int $userId): int;

    public function getTotalXpFor(int $userId): int;

    public function getCurrentStreakFor(int $userId): int;
}
