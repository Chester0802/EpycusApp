<?php

declare(strict_types=1);

namespace App\Modules\Wellbeing\Infrastructure\Repositories;

use App\Modules\Wellbeing\Domain\Contracts\WellbeingRepositoryInterface;
use App\Modules\Wellbeing\Infrastructure\Models\JournalEntryModel;
use Illuminate\Support\Collection;

final class EloquentWellbeingRepository implements WellbeingRepositoryInterface
{
    public function getEntriesForUserInMonth(int $userId, int $year, int $month): Collection
    {
        return JournalEntryModel::query()
            ->where('user_id', $userId)
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->orderBy('created_at')
            ->get();
    }

    public function getEntriesForUserOnDate(int $userId, string $date): Collection
    {
        return JournalEntryModel::query()
            ->where('user_id', $userId)
            ->where('date', $date)
            ->orderBy('created_at')
            ->get();
    }

    public function findByIdAndUser(int $id, int $userId): ?JournalEntryModel
    {
        return JournalEntryModel::query()
            ->where('id', $id)
            ->where('user_id', $userId)
            ->first();
    }

    public function create(array $data): JournalEntryModel
    {
        return JournalEntryModel::create($data);
    }

    public function update(JournalEntryModel $entry, array $data): JournalEntryModel
    {
        $entry->update($data);
        return $entry->fresh();
    }

    public function countEntriesForUserOnDate(int $userId, string $date): int
    {
        return JournalEntryModel::query()
            ->where('user_id', $userId)
            ->where('date', $date)
            ->count();
    }

    public function getEntriesForUserInRange(int $userId, string $from, string $to): Collection
    {
        return JournalEntryModel::query()
            ->where('user_id', $userId)
            ->whereBetween('date', [$from, $to])
            ->orderBy('date')
            ->get();
    }
}
