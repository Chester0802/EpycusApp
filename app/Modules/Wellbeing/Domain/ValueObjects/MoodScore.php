<?php

declare(strict_types=1);

namespace App\Modules\Wellbeing\Domain\ValueObjects;

final class MoodScore
{
    private const MIN = 1;
    private const MAX = 5;

    private function __construct(
        private int $value,
    ) {
        if ($value < self::MIN || $value > self::MAX) {
            throw new \DomainException("MoodScore must be between {self::MIN} and {self::MAX}, got {$value}.");
        }
    }

    public static function from(int $value): self
    {
        return new self($value);
    }

    public function value(): int
    {
        return $this->value;
    }

    public function emoji(): string
    {
        return match ($this->value) {
            1 => '😢',
            2 => '😟',
            3 => '😐',
            4 => '🙂',
            5 => '😄',
            default => '😐',
        };
    }

    public function label(): string
    {
        return match ($this->value) {
            1 => 'Muy mal',
            2 => 'Mal',
            3 => 'Normal',
            4 => 'Bien',
            5 => 'Muy bien',
            default => 'Normal',
        };
    }

    public static function all(): array
    {
        return array_map(fn (int $v) => [
            'value' => $v,
            'emoji' => self::from($v)->emoji(),
            'label' => self::from($v)->label(),
        ], range(self::MIN, self::MAX));
    }
}
