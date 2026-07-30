<?php

declare(strict_types=1);

namespace App\Modules\StudyGroups\Domain\ValueObjects;

use App\Modules\StudyGroups\Domain\Exceptions\InvalidRoomPhaseException;

final class RoomPhase
{
    private const VALID = ['idle', 'focus', 'break', 'completed'];

    private function __construct(
        private string $value,
    ) {
        if (! in_array($value, self::VALID, true)) {
            throw new InvalidRoomPhaseException($value);
        }
    }

    public const IDLE = 'idle';
    public const FOCUS = 'focus';
    public const BREAK = 'break';
    public const COMPLETED = 'completed';

    public static function idle(): self
    {
        return new self(self::IDLE);
    }

    public static function focus(): self
    {
        return new self(self::FOCUS);
    }

    public static function break(): self
    {
        return new self(self::BREAK);
    }

    public static function completed(): self
    {
        return new self(self::COMPLETED);
    }

    public static function from(string $value): self
    {
        return new self($value);
    }

    public function value(): string
    {
        return $this->value;
    }

    public function isIdle(): bool
    {
        return $this->value === self::IDLE;
    }

    public function isFocus(): bool
    {
        return $this->value === self::FOCUS;
    }

    public function isBreak(): bool
    {
        return $this->value === self::BREAK;
    }

    public function isCompleted(): bool
    {
        return $this->value === self::COMPLETED;
    }

    public function isRunning(): bool
    {
        return $this->value === self::FOCUS || $this->value === self::BREAK;
    }
}
