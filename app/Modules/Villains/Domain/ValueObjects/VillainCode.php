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
        'impostor_syndrome',
        'perfectionism',
        'isolation',
        'burnout',
        'all_nighter',
    ];

    private const IMAGE_MAP = [
        'procrastination' => 'villano-postergacion.png',
        'distraction' => 'villano-distraccion.png',
        'anxiety' => 'villano-ansiedad.png',
        'disorder' => 'villano-desorden.png',
        'fatigue' => 'villano-cansancio.png',
        'impostor_syndrome' => 'villano-sindromeImpostor.png',
        'perfectionism' => 'villano-perfeccionismoParalizante.png',
        'isolation' => 'villano-aislamientoAcademico.png',
        'burnout' => 'villano-sobrecarga.png',
        'all_nighter' => 'villano-ilusionUltimaNoche.png',
    ];

    private const WEAKNESS_MAP = [
        'procrastination' => ['mission', 'study_group'],
        'distraction' => ['pomodoro'],
        'anxiety' => ['habit', 'journal', 'study_group'],
        'disorder' => ['mission', 'study_group'],
        'fatigue' => ['habit'],
        'impostor_syndrome' => ['journal', 'mission'],
        'perfectionism' => ['pomodoro', 'mission'],
        'isolation' => ['study_group', 'ai_assistant'],
        'burnout' => ['habit', 'pomodoro'],
        'all_nighter' => ['mission', 'habit'],
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
