<?php

declare(strict_types=1);

namespace App\Modules\Identity\Domain\ValueObjects;

final readonly class ParticipantCode
{
    public function __construct(private string $value)
    {
        if (! preg_match('/^EPY-[A-Z0-9]{4}$/', $value)) {
            throw new \InvalidArgumentException("Formato de código inválido: $value");
        }
    }

    public function value(): string
    {
        return $this->value;
    }

    public static function generate(): self
    {
        $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $code = 'EPY-';
        for ($i = 0; $i < 4; $i++) {
            $code .= $chars[random_int(0, strlen($chars) - 1)];
        }

        return new self($code);
    }
}
