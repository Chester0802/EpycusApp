<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application\UseCases;

use App\Modules\Identity\Application\DTOs\RegisterUserDTO;
use App\Modules\Identity\Application\DTOs\UserDTO;
use App\Modules\Identity\Domain\Contracts\ParticipantRepositoryInterface;
use App\Modules\Identity\Domain\Contracts\UserRepositoryInterface;
use App\Modules\Identity\Domain\Entities\Participant;
use App\Modules\Identity\Domain\Entities\User;
use App\Modules\Identity\Domain\Events\UserRegistered;
use App\Modules\Identity\Domain\Exceptions\EmailAlreadyTakenException;
use App\Modules\Identity\Domain\ValueObjects\ParticipantCode;
use App\Modules\Identity\Domain\ValueObjects\UserId;
use App\Shared\Application\TransactionManagerInterface;
use Illuminate\Contracts\Events\Dispatcher;

final readonly class RegisterUserUseCase
{
    public function __construct(
        private UserRepositoryInterface $users,
        private ParticipantRepositoryInterface $participants,
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
            password: $dto->password,
            alias: $dto->alias,
        );

        $saved = $this->transaction->run(function () use ($user) {
            $this->users->save($user);
            $user = $this->users->findByEmail($user->email());

            $participant = new Participant(
                userId: $user->id(),
                participantCode: ParticipantCode::generate(),
            );
            $this->participants->save($participant);

            return $user;
        });

        // participant_code se genera al registrar (docs/01-MODULOS.md #1).
        // El consentimiento se otorga aparte, en un flujo posterior explícito.
        $this->events->dispatch(new UserRegistered(
            userId: $saved->id()->value(),
            email: $dto->email,
            alias: $dto->alias,
            occurredAt: new \DateTimeImmutable,
        ));

        return UserDTO::fromDomain($saved);
    }
}
