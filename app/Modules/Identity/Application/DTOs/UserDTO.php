<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application\DTOs;

use App\Modules\Identity\Domain\Entities\User;

final readonly class UserDTO
{
    public function __construct(
        public int $id,
        public string $name,
        public string $email,
        public string $alias,
        public ?string $career,
        public ?string $avatarStyle,
        public ?string $avatarGender,
        public ?int $cycle,
        public ?string $institutionType,
        public bool $profileCompleted,
    ) {}

    public static function fromDomain(User $user): self
    {
        return new self(
            id: $user->id()->value(),
            name: $user->name(),
            email: $user->email(),
            alias: $user->alias(),
            career: $user->career()?->value(),
            avatarStyle: $user->avatarStyle()?->value(),
            avatarGender: $user->avatarGender()?->value(),
            cycle: $user->cycle()?->value(),
            institutionType: $user->institutionType()?->value(),
            profileCompleted: $user->hasCompletedProfile(),
        );
    }
}
