<?php

declare(strict_types=1);

namespace App\Modules\StudyGroups\Application\UseCases;

use App\Modules\StudyGroups\Application\DTOs\SendMessageDTO;
use App\Modules\StudyGroups\Application\Mappers\StudyGroupMapper;
use App\Modules\StudyGroups\Domain\Contracts\StudySessionRepositoryInterface;
use App\Modules\StudyGroups\Domain\Events\GroupMessageSent;
use App\Modules\StudyGroups\Domain\Exceptions\ChatBlockedException;
use App\Modules\StudyGroups\Domain\Exceptions\MessageBlockedException;
use App\Modules\StudyGroups\Domain\Exceptions\NotInSessionException;
use App\Modules\StudyGroups\Domain\ValueObjects\MessageBody;
use App\Modules\StudyGroups\Domain\ValueObjects\RoomPhase;
use Illuminate\Contracts\Events\Dispatcher;

final readonly class SendMessageUseCase
{
    public function __construct(
        private StudySessionRepositoryInterface $repository,
        private StudyGroupMapper $mapper,
        private Dispatcher $events,
    ) {}

    /** @return array<string, mixed> */
    public function execute(SendMessageDTO $dto): array
    {
        $session = $this->repository->findById($dto->sessionId);
        if (! $session) {
            throw new \App\Modules\StudyGroups\Domain\Exceptions\SessionNotFoundException($dto->sessionId);
        }

        $phase = RoomPhase::from($session->phase ?? 'idle');
        if ($phase->isFocus()) {
            throw new ChatBlockedException;
        }

        $body = new MessageBody($dto->body);

        if ($body->containsBlockedWords()) {
            throw new MessageBlockedException;
        }

        if (! $this->repository->isUserInSession($dto->sessionId, $dto->userId)) {
            throw new NotInSessionException($dto->sessionId);
        }

        $message = $this->repository->saveMessage(
            $dto->sessionId,
            $dto->userId,
            $body->value,
            now()->toDateTimeString(),
        );

        $this->events->dispatch(new GroupMessageSent(
            userId: $dto->userId,
            sessionId: $dto->sessionId,
            messageId: $message->id,
            messageLength: $body->length(),
            occurredAt: new \DateTimeImmutable,
        ));

        return $this->mapper->toMessageArray($message);
    }
}
