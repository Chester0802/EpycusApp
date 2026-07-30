<?php

declare(strict_types=1);

namespace App\Modules\Wellbeing\Application\UseCases;

use App\Modules\Wellbeing\Application\DTOs\CreateEntryDTO;
use App\Modules\Wellbeing\Domain\Contracts\WellbeingRepositoryInterface;
use App\Modules\Wellbeing\Domain\Events\JournalEntryCreated;
use App\Modules\Wellbeing\Domain\ValueObjects\MoodScore;
use Illuminate\Contracts\Events\Dispatcher;

final class CreateEntryUseCase
{
    public function __construct(
        private WellbeingRepositoryInterface $repository,
        private Dispatcher $events,
    ) {}

    public function execute(CreateEntryDTO $dto): array
    {
        MoodScore::from($dto->moodScore);

        $entry = $this->repository->create([
            'user_id' => $dto->userId,
            'date' => $dto->date,
            'mood_score' => $dto->moodScore,
            'energy' => $dto->energy,
            'stress' => $dto->stress,
            'sleep_hours' => $dto->sleepHours,
            'physical_activity' => $dto->physicalActivity,
            'content' => $dto->content,
            'tags' => ! empty($dto->tags) ? $dto->tags : null,
        ]);

        $isFirstToday = $this->repository->countEntriesForUserOnDate($dto->userId, $dto->date) === 1;

        $this->events->dispatch(new JournalEntryCreated(
            entry: $entry,
            occurredAt: new \DateTimeImmutable,
        ));

        return [
            'id' => $entry->id,
            'date' => $entry->date->toDateString(),
            'mood_score' => $entry->mood_score,
            'energy' => $entry->energy,
            'stress' => $entry->stress,
            'sleep_hours' => $entry->sleep_hours,
            'physical_activity' => $entry->physical_activity,
            'content' => $entry->content,
            'tags' => $entry->tags ?? [],
            'created_at' => $entry->created_at->toIso8601String(),
            'xp_awarded' => $isFirstToday ? config('gamification.xp.journal_entry', 10) : 0,
        ];
    }
}
