<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application\UseCases;

use App\Modules\Identity\Application\DTOs\CompleteProfileDTO;
use App\Modules\Identity\Application\DTOs\UserDTO;
use App\Modules\Identity\Domain\Contracts\UserRepositoryInterface;
use App\Modules\Identity\Domain\Events\ProfileCompleted;
use App\Modules\Identity\Domain\Exceptions\UserNotFoundException;
use App\Modules\Identity\Domain\ValueObjects\AvatarGender;
use App\Modules\Identity\Domain\ValueObjects\AvatarStyle;
use App\Modules\Identity\Domain\ValueObjects\Career;
use App\Modules\Identity\Domain\ValueObjects\Cycle;
use App\Modules\Identity\Domain\ValueObjects\InstitutionType;
use App\Modules\Identity\Domain\ValueObjects\UserId;
use App\Shared\Application\TransactionManagerInterface;
use Illuminate\Contracts\Events\Dispatcher;

final readonly class CompleteProfileUseCase
{
    public function __construct(
        private UserRepositoryInterface $users,
        private TransactionManagerInterface $transaction,
        private Dispatcher $events,
    ) {}

    public function execute(CompleteProfileDTO $dto): UserDTO
    {
        $user = $this->users->findById(new UserId($dto->userId))
            ?? throw new UserNotFoundException($dto->userId);

        $user->completeProfile(
            career: new Career($dto->career),
            avatarStyle: new AvatarStyle($dto->avatarStyle),
            avatarGender: new AvatarGender($dto->avatarGender),
            cycle: new Cycle($dto->cycle),
            institutionType: new InstitutionType($dto->institutionType),
        );

        $saved = $this->transaction->run(function () use ($user) {
            $this->users->save($user);

            return $user;
        });

        $this->events->dispatch(new ProfileCompleted(
            userId: $dto->userId,
            career: $dto->career,
            avatarStyle: $dto->avatarStyle,
            occurredAt: new \DateTimeImmutable,
        ));

        return UserDTO::fromDomain($saved);
    }
}
