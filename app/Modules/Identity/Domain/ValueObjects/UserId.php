<?php

declare(strict_types=1);

namespace App\Modules\Identity\Domain\ValueObjects;

final readonly class UserId
{
    public function __construct(private int $value) {}

    public function value(): int
    {
        return $this->value;
    }

    public function equals(UserId $other): bool
    {
        return $this->value === $other->value;
    }
}
