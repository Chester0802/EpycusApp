<?php

declare(strict_types=1);

namespace App\Modules\Identity\Domain\Entities;

use App\Modules\Identity\Domain\ValueObjects\AvatarGender;
use App\Modules\Identity\Domain\ValueObjects\AvatarStyle;
use App\Modules\Identity\Domain\ValueObjects\Career;
use App\Modules\Identity\Domain\ValueObjects\Cycle;
use App\Modules\Identity\Domain\ValueObjects\InstitutionType;
use App\Modules\Identity\Domain\ValueObjects\UserId;

final class User
{
    public function __construct(
        private UserId $id,
        private string $name,
        private string $email,
        private string $alias,
        private ?Career $career = null,
        private ?AvatarStyle $avatarStyle = null,
        private ?AvatarGender $avatarGender = null,
        private ?Cycle $cycle = null,
        private ?InstitutionType $institutionType = null,
    ) {}

    public function id(): UserId
    {
        return $this->id;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function email(): string
    {
        return $this->email;
    }

    public function alias(): string
    {
        return $this->alias;
    }

    public function career(): ?Career
    {
        return $this->career;
    }

    public function avatarStyle(): ?AvatarStyle
    {
        return $this->avatarStyle;
    }

    public function avatarGender(): ?AvatarGender
    {
        return $this->avatarGender;
    }

    public function cycle(): ?Cycle
    {
        return $this->cycle;
    }

    public function institutionType(): ?InstitutionType
    {
        return $this->institutionType;
    }

    public function hasCompletedProfile(): bool
    {
        return $this->career !== null
            && $this->avatarStyle !== null
            && $this->avatarGender !== null
            && $this->cycle !== null
            && $this->institutionType !== null;
    }

    public function completeProfile(
        Career $career,
        AvatarStyle $avatarStyle,
        AvatarGender $avatarGender,
        Cycle $cycle,
        InstitutionType $institutionType,
    ): void {
        $this->career = $career;
        $this->avatarStyle = $avatarStyle;
        $this->avatarGender = $avatarGender;
        $this->cycle = $cycle;
        $this->institutionType = $institutionType;
    }
}
