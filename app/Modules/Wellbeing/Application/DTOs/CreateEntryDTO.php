<?php

declare(strict_types=1);

namespace App\Modules\Wellbeing\Application\DTOs;

final class CreateEntryDTO
{
    /**
     * @param string[] $tags
     * @param array<string, mixed>|null $physicalActivity
     */
    public function __construct(
        public int $userId,
        public string $date,
        public int $moodScore,
        public ?int $energy = null,
        public ?int $stress = null,
        public ?float $sleepHours = null,
        public ?array $physicalActivity = null,
        public ?string $content = null,
        public array $tags = [],
    ) {}
}
