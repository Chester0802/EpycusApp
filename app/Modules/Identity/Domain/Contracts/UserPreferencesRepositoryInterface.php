<?php

declare(strict_types=1);

namespace App\Modules\Identity\Domain\Contracts;

use App\Modules\Identity\Domain\Entities\UserPreferences;
use App\Modules\Identity\Domain\ValueObjects\UserId;

interface UserPreferencesRepositoryInterface
{
    public function findByUserId(UserId $userId): ?UserPreferences;

    public function save(UserPreferences $preferences): void;
}
