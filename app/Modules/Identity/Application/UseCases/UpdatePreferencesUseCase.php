<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application\UseCases;

use App\Modules\Identity\Application\DTOs\UpdatePreferencesDTO;
use App\Modules\Identity\Application\DTOs\UserPreferencesDTO;
use App\Modules\Identity\Domain\Contracts\UserPreferencesRepositoryInterface;
use App\Modules\Identity\Domain\Exceptions\UserPreferencesNotFoundException;
use App\Modules\Identity\Domain\ValueObjects\SurfaceMode;
use App\Modules\Identity\Domain\ValueObjects\UserId;
use App\Shared\Application\TransactionManagerInterface;

final readonly class UpdatePreferencesUseCase
{
    public function __construct(
        private UserPreferencesRepositoryInterface $preferences,
        private TransactionManagerInterface $transaction,
    ) {}

    public function execute(UpdatePreferencesDTO $dto): UserPreferencesDTO
    {
        $userId = new UserId($dto->userId);

        $preferences = $this->preferences->findByUserId($userId)
            ?? throw new UserPreferencesNotFoundException($dto->userId);

        if ($dto->surfaceMode !== null) {
            $preferences->changeSurfaceMode(new SurfaceMode($dto->surfaceMode));
        }

        if ($dto->notificationsEnabled !== null) {
            $dto->notificationsEnabled
                ? $preferences->enableNotifications()
                : $preferences->disableNotifications();
        }

        if ($dto->notificationSettings !== null) {
            $preferences->changeNotificationSettings($dto->notificationSettings);
        }

        $saved = $this->transaction->run(function () use ($preferences) {
            $this->preferences->save($preferences);

            return $preferences;
        });

        return UserPreferencesDTO::fromDomain($saved);
    }
}
