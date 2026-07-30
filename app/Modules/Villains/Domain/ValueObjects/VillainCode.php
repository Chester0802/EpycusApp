<?php

declare(strict_types=1);

namespace App\Modules\Villains\Domain\ValueObjects;

final class VillainCode
{
    private const VALID = [
        'procrastination',
        'distraction',
        'anxiety',
        'disorder',
        'fatigue',
    ];

    private const IMAGE_MAP = [
        'procrastination' => 'Villano_postergación.png',
        'distraction' => 'Villano_distracción.png',
        'anxiety' => 'Villano_ansiedad.png',
        'disorder' => 'Villano_desorden.png',
        'fatigue' => 'Villano_cansancio.png',
    ];

    private const WEAKNESS_MAP = [
        'procrastination' => ['mission'],
        'distraction' => ['pomodoro'],
        'anxiety' => ['habit', 'journal'],
        'disorder' => ['mission'],
        'fatigue' => ['habit'],
    ];

    private function __construct(
        private string $value,
    ) {
        if (! in_array($value, self::VALID, true)) {
            throw new \DomainException("Invalid villain code: {$value}");
        }
    }

    public static function from(string $value): self
    {
        return new self($value);
    }

    public function value(): string
    {
        return $this->value;
    }

    public function imageFilename(): string
    {
        return self::IMAGE_MAP[$this->value];
    }

    public function imagePath(): string
    {
        return 'assets/villains/'.$this->imageFilename();
    }

    public function isWeakTo(string $sourceType): bool
    {
        return in_array($sourceType, self::WEAKNESS_MAP[$this->value], true);
    }

    /** @return string[] */
    public static function all(): array
    {
        return self::VALID;
    }
}
