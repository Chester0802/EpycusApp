<?php

declare(strict_types=1);

namespace App\Modules\Habits\Application\DTOs;

final readonly class UpdateHabitDTO
{
    /**
     * @param  array<string, mixed>  $frequency
     */
    public function __construct(
        public int $habitId,
        public int $userId,
        public string $title,
        public string $category,
        public array $frequency,
        public ?string $icon = null,
        public string $timeOfDay = 'anytime',
        public ?string $cueTrigger = null,
        public string $habitType = 'build',
        public ?int $maxPerWeek = null,
    ) {}
}
