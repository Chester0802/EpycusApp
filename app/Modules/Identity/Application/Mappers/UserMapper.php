<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application\Mappers;

use App\Modules\Identity\Domain\Entities\User;
use App\Modules\Identity\Domain\ValueObjects\AvatarGender;
use App\Modules\Identity\Domain\ValueObjects\AvatarStyle;
use App\Modules\Identity\Domain\ValueObjects\Career;
use App\Modules\Identity\Domain\ValueObjects\Cycle;
use App\Modules\Identity\Domain\ValueObjects\InstitutionType;
use App\Modules\Identity\Domain\ValueObjects\UserId;
use App\Modules\Identity\Infrastructure\Models\UserModel;

final class UserMapper
{
    public function toDomain(UserModel $model): User
    {
        return new User(
            id: new UserId($model->id),
            name: $model->name,
            email: $model->email,
            password: $model->password,
            alias: $model->alias,
            career: $model->career ? new Career($model->career) : null,
            avatarStyle: $model->avatar_style ? new AvatarStyle($model->avatar_style) : null,
            avatarGender: $model->avatar_gender ? new AvatarGender($model->avatar_gender) : null,
            cycle: $model->cycle ? new Cycle($model->cycle) : null,
            institutionType: $model->institution_type ? new InstitutionType($model->institution_type) : null,
        );
    }

    public function toPersistence(User $user): array
    {
        return [
            'id' => $user->id()->value(),
            'name' => $user->name(),
            'email' => $user->email(),
            'password' => $user->password(),
            'alias' => $user->alias(),
            'career' => $user->career()?->value(),
            'avatar_style' => $user->avatarStyle()?->value(),
            'avatar_gender' => $user->avatarGender()?->value(),
            'cycle' => $user->cycle()?->value(),
            'institution_type' => $user->institutionType()?->value(),
        ];
    }
}
