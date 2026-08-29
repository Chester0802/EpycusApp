<?php

declare(strict_types=1);

namespace App\Modules\Pomodoro\Domain\ValueObjects;

final readonly class SessionState
{
    public const RUNNING = 'running';

    public const PAUSED = 'paused';

    public const COMPLETED = 'completed';

    public const ABANDONED = 'abandoned';

    private const ALL = [self::RUNNING, self::PAUSED, self::COMPLETED, self::ABANDONED];

    public function __construct(private string $value)
    {
        if (! in_array($value, self::ALL, true)) {
            throw new \InvalidArgumentException("Estado de Pomodoro no válido: {$value}");
        }
    }

    public function value(): string
    {
        return $this->value;
    }

    public function isActive(): bool
    {
        return in_array($this->value, [self::RUNNING, self::PAUSED], true);
    }
}
