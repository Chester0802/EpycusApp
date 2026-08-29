<?php

declare(strict_types=1);

namespace App\Modules\Identity\Infrastructure\Repositories;

use App\Modules\Identity\Application\Mappers\UserPreferencesMapper;
use App\Modules\Identity\Domain\Contracts\UserPreferencesRepositoryInterface;
use App\Modules\Identity\Domain\Entities\UserPreferences;
use App\Modules\Identity\Domain\ValueObjects\UserId;
use App\Modules\Identity\Infrastructure\Models\UserPreferencesModel;

final readonly class EloquentUserPreferencesRepository implements UserPreferencesRepositoryInterface
{
    public function __construct(private UserPreferencesMapper $mapper) {}

    public function findByUserId(UserId $userId): ?UserPreferences
    {
        $model = UserPreferencesModel::where('user_id', $userId->value())->first();

        return $model ? $this->mapper->toDomain($model) : null;
    }

    public function save(UserPreferences $preferences): void
    {
        $data = $this->mapper->toPersistence($preferences);
        unset($data['user_id']);

        UserPreferencesModel::updateOrCreate(
            ['user_id' => $preferences->userId()->value()],
            $data,
        );
    }
}
