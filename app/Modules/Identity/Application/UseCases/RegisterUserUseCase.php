<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application\UseCases;

use App\Modules\Identity\Application\DTOs\RegisterUserDTO;
use App\Modules\Identity\Application\DTOs\UserDTO;
use App\Modules\Identity\Domain\Contracts\UserRepositoryInterface;
use App\Modules\Identity\Domain\Entities\User;
use App\Modules\Identity\Domain\Events\UserRegistered;
use App\Modules\Identity\Domain\Exceptions\EmailAlreadyTakenException;
use App\Modules\Identity\Domain\ValueObjects\UserId;
use App\Shared\Application\TransactionManagerInterface;
use Illuminate\Contracts\Events\Dispatcher;

final readonly class RegisterUserUseCase
{
    public function __construct(
        private UserRepositoryInterface $users,
        private TransactionManagerInterface $transaction,
        private Dispatcher $events,
    ) {}

    public function execute(RegisterUserDTO $dto): UserDTO
    {
        if ($this->users->findByEmail($dto->email) !== null) {
            throw new EmailAlreadyTakenException($dto->email);
        }

        $user = new User(
            id: new UserId(0),
            name: $dto->name,
            email: $dto->email,
            alias: $dto->alias,
        );

        $saved = $this->transaction->run(function () use ($user) {
            $this->users->save($user);

            return $user;
        });

        $this->events->dispatch(new UserRegistered(
            userId: $saved->id()->value(),
            email: $dto->email,
            alias: $dto->alias,
            occurredAt: new \DateTimeImmutable,
        ));

        return UserDTO::fromDomain($saved);
    }
}
