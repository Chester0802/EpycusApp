<?php

declare(strict_types=1);

namespace App\Modules\Identity\Domain\Entities;

use App\Modules\Identity\Domain\Exceptions\ConsentAlreadyGrantedException;
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

    public function studentCode(): ?string
    {
        return $this->studentCode;
    }

    public function whatsapp(): ?string
    {
        return $this->whatsapp;
    }

    public function enrolledAt(): ?\DateTimeImmutable
    {
        return $this->enrolledAt;
    }

    public function consentGrantedAt(): ?\DateTimeImmutable
    {
        return $this->consentGrantedAt;
    }

    public function withdrawnAt(): ?\DateTimeImmutable
    {
        return $this->withdrawnAt;
    }

    public function grantConsent(): void
    {
        // El timestamp de consentimiento es un registro de ética/estudio:
        // nunca se reescribe una vez otorgado.
        if ($this->hasConsented()) {
            throw new ConsentAlreadyGrantedException($this->userId->value());
        }

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
