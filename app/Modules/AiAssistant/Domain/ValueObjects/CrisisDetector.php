<?php

declare(strict_types=1);

namespace App\Modules\AiAssistant\Domain\ValueObjects;

final class CrisisDetector
{
    private const CRISIS_KEYWORDS = [
        'suicidar',
        'suicidarme',
        'suicidio',
        'autolesion',
        'autolesionarme',
        'cortarme',
        'no quiero vivir',
        'acabar con todo',
        'matarme',
        'quitarme la vida',
        'no vale la pena vivir',
        'morirme',
        'no aguanto mas la vida',
    ];

    public static function isCrisis(string $text): bool
    {
        $normalized = mb_strtolower($text, 'UTF-8');
        foreach (self::CRISIS_KEYWORDS as $keyword) {
            if (str_contains($normalized, $keyword)) {
                return true;
            }
        }

        return false;
    }

    public static function containmentMessage(): string
    {
        return 'Tu vida y bienestar son lo más importante. Si estás pasando por un momento muy difícil o sientes angustia severa, por favor busca ayuda de inmediato.'."\n\n".
            'Puedes comunicarte de forma gratuita y confidencial desde cualquier teléfono en el Perú a la **Línea 113 (Opción 5)** de Salud Mental del MINSA, o acudir al centro de salud o centro de salud mental comunitario más cercano. No estás solo/a en esto.';
    }
}
