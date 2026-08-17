<?php

declare(strict_types=1);

namespace App\Modules\Missions\Application\DTOs;

final readonly class CreateMissionDTO
{
    public function __construct(
        public int $userId,
        public string $title,
        public ?string $description,
        public string $difficulty,
        public string $priority,
        public ?string $dueDate,
        /** @var array<int, string> */
        public array $subtasks,
        public ?string $eisenhowerQuadrant = 'q2',
    ) {}
}
