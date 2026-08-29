<?php

declare(strict_types=1);

namespace App\Modules\Wellbeing\Application\UseCases;

use App\Modules\Wellbeing\Application\DTOs\EditEntryDTO;
use App\Modules\Wellbeing\Domain\Contracts\WellbeingRepositoryInterface;
use App\Modules\Wellbeing\Domain\Events\JournalEntryEdited;
use App\Modules\Wellbeing\Domain\ValueObjects\MoodScore;
use Illuminate\Contracts\Events\Dispatcher;

final class EditEntryUseCase
{
    public function __construct(
        private WellbeingRepositoryInterface $repository,
        private Dispatcher $events,
    ) {}

    public function execute(EditEntryDTO $dto): array
    {
        MoodScore::from($dto->moodScore);

        $entry = $this->repository->findByIdAndUser($dto->entryId, $dto->userId);

        if (! $entry) {
            throw new \RuntimeException('Journal entry not found.');
        }

        $entry = $this->repository->update($entry, [
            'mood_score' => $dto->moodScore,
            'energy' => $dto->energy,
            'stress' => $dto->stress,
            'sleep_hours' => $dto->sleepHours,
            'physical_activity' => $dto->physicalActivity,
            'content' => $dto->content,
            'tags' => ! empty($dto->tags) ? $dto->tags : null,
        ]);

        $this->events->dispatch(new JournalEntryEdited(
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
            'updated_at' => $entry->updated_at->toIso8601String(),
        ];
    }
}
