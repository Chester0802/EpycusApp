<?php

declare(strict_types=1);

namespace App\Modules\Wellbeing\Domain\Contracts;

use App\Modules\Wellbeing\Infrastructure\Models\JournalEntryModel;
use Illuminate\Support\Collection;

interface WellbeingRepositoryInterface
{
    /** @return Collection<int, JournalEntryModel> */
    public function getEntriesForUserInMonth(int $userId, int $year, int $month): Collection;

    /** @return Collection<int, JournalEntryModel> */
    public function getEntriesForUserOnDate(int $userId, string $date): Collection;

    public function findByIdAndUser(int $id, int $userId): ?JournalEntryModel;

    public function create(array $data): JournalEntryModel;

    public function update(JournalEntryModel $entry, array $data): JournalEntryModel;

    public function countEntriesForUserOnDate(int $userId, string $date): int;

    /** @return Collection<int, JournalEntryModel> */
    public function getEntriesForUserInRange(int $userId, string $from, string $to): Collection;
}
