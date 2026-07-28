<?php

declare(strict_types=1);

namespace App\Modules\Identity\Infrastructure\Repositories;

use App\Modules\Identity\Application\Mappers\UserMapper;
use App\Modules\Identity\Domain\Contracts\UserRepositoryInterface;
use App\Modules\Identity\Domain\Entities\User;
use App\Modules\Identity\Domain\ValueObjects\UserId;
use App\Modules\Identity\Infrastructure\Models\UserModel;

final readonly class EloquentUserRepository implements UserRepositoryInterface
{
    public function __construct(private UserMapper $mapper) {}

    public function findById(UserId $id): ?User
    {
        $model = UserModel::find($id->value());

        return $model ? $this->mapper->toDomain($model) : null;
    }

    public function findByEmail(string $email): ?User
    {
        $model = UserModel::where('email', $email)->first();

        return $model ? $this->mapper->toDomain($model) : null;
    }

    public function save(User $user): void
    {
        UserModel::updateOrCreate(
            ['id' => $user->id()->value()],
            $this->mapper->toPersistence($user),
        );
    }

    public function delete(UserId $id): void
    {
        UserModel::where('id', $id->value())->delete();
    }
}
