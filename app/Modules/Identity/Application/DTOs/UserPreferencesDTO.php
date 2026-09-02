<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application\DTOs;

use App\Modules\Identity\Domain\Entities\UserPreferences;

final readonly class UserPreferencesDTO
{
    public function __construct(
        public int $userId,
        public string $surfaceMode,
        public bool $notificationsEnabled,
        public ?array $notificationSettings = null,
    ) {}

    public static function fromDomain(UserPreferences $preferences): self
    {
        return new self(
            userId: $preferences->userId()->value(),
            surfaceMode: $preferences->surfaceMode()->value(),
            notificationsEnabled: $preferences->notificationsEnabled(),
            notificationSettings: $preferences->notificationSettings(),
        );
    }
}
