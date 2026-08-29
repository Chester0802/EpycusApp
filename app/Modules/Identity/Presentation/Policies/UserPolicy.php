<?php

declare(strict_types=1);

namespace App\Modules\Identity\Presentation\Policies;

use App\Modules\Identity\Infrastructure\Models\UserModel;

final class UserPolicy
{
    public function view(UserModel $user, UserModel $target): bool
    {
        return $user->id === $target->id;
    }

    public function update(UserModel $user, UserModel $target): bool
    {
        return $user->id === $target->id;
    }
}
