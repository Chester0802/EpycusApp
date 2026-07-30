<?php

declare(strict_types=1);

namespace App\Modules\Motivation\Application\UseCases;

use App\Modules\Motivation\Infrastructure\Models\MotivationalQuoteModel;
use App\Modules\Motivation\Infrastructure\Models\UserQuoteViewModel;
use App\Shared\Domain\Services\NoRepeatPicker;

final class GetQuoteForLoginUseCase
{
    public function __construct(
        private readonly NoRepeatPicker $picker
    ) {}

    public function execute(int $userId): ?array
    {
        // 1. Si ya hay una frase en la sesión actual, devolver la misma
        $sessionQuoteId = session('epycus_login_quote_id');
        if ($sessionQuoteId) {
            $quote = MotivationalQuoteModel::find($sessionQuoteId);
            if ($quote) {
                return [
                    'id' => $quote->id,
                    'text' => $quote->text,
                    'author' => $quote->author,
                    'is_verified' => $quote->is_verified,
                ];
            }
        }

        // 2. Obtener IDs de todas las frases disponibles
        $allQuoteIds = MotivationalQuoteModel::pluck('id')->toArray();
        if (empty($allQuoteIds)) {
            return null;
        }

        // 3. Obtener IDs de frases ya mostradas a este usuario
        $alreadyShownIds = UserQuoteViewModel::where('user_id', $userId)
            ->pluck('quote_id')
            ->toArray();

        // 4. Elegir con NoRepeatPicker
        $selectedId = $this->picker->pick($allQuoteIds, $alreadyShownIds);
        if (!$selectedId) {
            return null;
        }

        // 5. Registrar visualización en BD y en la sesión actual
        UserQuoteViewModel::create([
            'user_id' => $userId,
            'quote_id' => $selectedId,
        ]);

        session(['epycus_login_quote_id' => $selectedId]);

        $quote = MotivationalQuoteModel::find($selectedId);

        return [
            'id' => $quote->id,
            'text' => $quote->text,
            'author' => $quote->author,
            'is_verified' => $quote->is_verified,
        ];
    }
}
