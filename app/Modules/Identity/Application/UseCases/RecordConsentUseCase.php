<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application\UseCases;

use App\Modules\Identity\Application\DTOs\RecordConsentDTO;
use App\Modules\Identity\Domain\Contracts\ParticipantRepositoryInterface;
use App\Modules\Identity\Domain\Events\ConsentGranted;
use App\Modules\Identity\Domain\Exceptions\ParticipantNotFoundException;
use App\Modules\Identity\Domain\ValueObjects\UserId;
use App\Shared\Application\TransactionManagerInterface;
use Illuminate\Contracts\Events\Dispatcher;

final readonly class RecordConsentUseCase
{
    public function __construct(
        private ParticipantRepositoryInterface $participants,
        private TransactionManagerInterface $transaction,
        private Dispatcher $events,
    ) {}

    public function execute(RecordConsentDTO $dto): void
    {
        $userId = new UserId($dto->userId);

        $participant = $this->participants->findByUserId($userId)
            ?? throw new ParticipantNotFoundException($dto->userId);

        $saved = $this->transaction->run(function () use ($participant) {
            $participant->grantConsent();
            $this->participants->save($participant);

            return $participant;
        });

        $this->events->dispatch(new ConsentGranted(
            userId: $dto->userId,
            participantCode: $saved->participantCode()->value(),
            occurredAt: new \DateTimeImmutable,
        ));
    }
}
