<?php

declare(strict_types=1);

namespace App\Modules\StudyGroups\Domain\ValueObjects;

final readonly class MessageBody
{
    private const MAX_LENGTH = 500;

    private const BLOCKED_WORDS = [
        'spam', 'xxx', 'puta', 'puto', 'mierda', 'concha',
    ];

    public function __construct(
        public string $value,
    ) {
        if (mb_strlen($value) < 1) {
            throw new \DomainException('El mensaje no puede estar vacío.');
        }
        if (mb_strlen($value) > self::MAX_LENGTH) {
            throw new \DomainException("El mensaje no puede exceder los {$this->maxLength()} caracteres.");
        }
    }

    public function maxLength(): int
    {
        return self::MAX_LENGTH;
    }

    public function containsBlockedWords(): bool
    {
        $lower = mb_strtolower($this->value);
        foreach (self::BLOCKED_WORDS as $word) {
            if (str_contains($lower, $word)) {
                return true;
            }
        }

        return false;
    }

    public function length(): int
    {
        return mb_strlen($this->value);
    }
}
