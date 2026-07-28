<?php

declare(strict_types=1);

namespace App\Modules\Identity\Domain\Entities;

use App\Modules\Identity\Domain\ValueObjects\ParticipantCode;
use App\Modules\Identity\Domain\ValueObjects\UserId;

final class Participant
{
    public function __construct(
        private UserId $userId,
        private ParticipantCode $participantCode,
        private ?string $studentCode = null,
        private ?string $whatsapp = null,
        private ?\DateTimeImmutable $consentGrantedAt = null,
        private ?\DateTimeImmutable $enrolledAt = null,
        private ?\DateTimeImmutable $withdrawnAt = null,
    ) {}

    public function userId(): UserId
    {
        return $this->userId;
    }

    public function participantCode(): ParticipantCode
    {
        return $this->participantCode;
    }

    public function grantConsent(): void
    {
        $this->consentGrantedAt = new \DateTimeImmutable;
    }

    public function hasConsented(): bool
    {
        return $this->consentGrantedAt !== null;
    }

    public function withdraw(): void
    {
        $this->withdrawnAt = new \DateTimeImmutable;
    }

    public function isActive(): bool
    {
        return $this->withdrawnAt === null;
    }
}
