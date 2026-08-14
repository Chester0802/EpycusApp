<?php

declare(strict_types=1);

namespace App\Modules\StudyGroups\Domain\ValueObjects;

final class SessionState
{
    private const VALID = ['open', 'running', 'completed', 'closed'];

    private function __construct(
        private string $value,
    ) {
        if (! in_array($value, self::VALID, true)) {
            throw new \DomainException("Invalid session state: {$value}");
        }
    }

    public const OPEN = 'open';

    public const RUNNING = 'running';

    public const COMPLETED = 'completed';

    public const CLOSED = 'closed';

    public static function open(): self
    {
        return new self(self::OPEN);
    }

    public static function running(): self
    {
        return new self(self::RUNNING);
    }

    public static function completed(): self
    {
        return new self(self::COMPLETED);
    }

    public static function closed(): self
    {
        return new self(self::CLOSED);
    }

    public static function from(string $value): self
    {
        return new self($value);
    }

    public function value(): string
    {
        return $this->value;
    }

    public function isOpen(): bool
    {
        return $this->value === self::OPEN;
    }

    public function isRunning(): bool
    {
        return $this->value === self::RUNNING;
    }

    public function isCompleted(): bool
    {
        return $this->value === self::COMPLETED;
    }

    public function isClosed(): bool
    {
        return $this->value === self::CLOSED;
    }

    public function canJoin(): bool
    {
        return $this->value === self::OPEN || $this->value === self::RUNNING;
    }
}
