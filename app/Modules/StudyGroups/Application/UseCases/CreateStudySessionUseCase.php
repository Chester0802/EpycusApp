<?php

declare(strict_types=1);

namespace App\Modules\StudyGroups\Application\UseCases;

use App\Modules\StudyGroups\Application\DTOs\CreateSessionDTO;
use App\Modules\StudyGroups\Application\Mappers\StudyGroupMapper;
use App\Modules\StudyGroups\Domain\Contracts\StudySessionRepositoryInterface;
use App\Modules\StudyGroups\Domain\Events\StudySessionCreated;
use App\Modules\StudyGroups\Domain\Exceptions\InvalidRoomConfigException;
use App\Shared\Application\TransactionManagerInterface;
use Illuminate\Contracts\Events\Dispatcher;

final readonly class CreateStudySessionUseCase
{
    public function __construct(
        private StudySessionRepositoryInterface $repository,
        private StudyGroupMapper $mapper,
        private TransactionManagerInterface $transaction,
        private Dispatcher $events,
    ) {}

    /** @return array<string, mixed> */
    public function execute(CreateSessionDTO $dto): array
    {
        $name = trim($dto->name);
        if ($name === '') {
            throw new InvalidRoomConfigException('El nombre de la sesión no puede estar vacío.');
        }
        if (mb_strlen($name) > 80) {
            throw new InvalidRoomConfigException('El nombre no puede superar los 80 caracteres.');
        }

        $result = $this->transaction->run(function () use ($dto, $name) {
            return $this->repository->create([
                'host_id' => $dto->hostId,
                'name' => $name,
                'max_seats' => max(2, min(5, $dto->maxSeats)),
                'focus_minutes' => max(5, min(120, $dto->focusMinutes)),
                'break_minutes' => max(1, min(30, $dto->breakMinutes)),
                'cycles' => max(1, min(20, $dto->cycles)),
                'state' => 'open',
            ]);
        });

        $this->repository->addParticipant(
            $result->id,
            $dto->hostId,
            now()->toDateTimeString(),
        );

        $this->events->dispatch(new StudySessionCreated(
            userId: $dto->hostId,
            sessionId: $result->id,
            sessionName: $dto->name,
            occurredAt: new \DateTimeImmutable,
        ));

        return $this->mapper->toSessionArray($result);
    }
}
