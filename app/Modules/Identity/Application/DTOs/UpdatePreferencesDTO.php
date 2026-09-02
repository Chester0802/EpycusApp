<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application\DTOs;

final readonly class UpdatePreferencesDTO
{
    public function __construct(
        public int $userId,
        public ?string $surfaceMode = null,
        public ?bool $notificationsEnabled = null,
        public ?array $notificationSettings = null,
    ) {}
}
