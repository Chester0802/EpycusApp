<?php

declare(strict_types=1);

namespace App\Modules\Missions\Application\DTOs;

final readonly class UpdateMissionDTO
{
    public function __construct(
        public int $missionId,
        public int $userId,
        public string $title,
        public ?string $description,
        public string $difficulty,
        public string $priority,
        public ?string $dueDate,
        public ?string $eisenhowerQuadrant = null,
        public ?int $courseId = null,
    ) {}
}
