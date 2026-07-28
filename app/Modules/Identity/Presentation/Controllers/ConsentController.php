<?php

declare(strict_types=1);

namespace App\Modules\Identity\Presentation\Controllers;

use App\Modules\Identity\Application\DTOs\RecordConsentDTO;
use App\Modules\Identity\Application\UseCases\RecordConsentUseCase;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final readonly class ConsentController
{
    public function __construct(private RecordConsentUseCase $recordConsent) {}

    /**
     * Muestra la pantalla de consentimiento informado.
     * Ruta GET /consent — agregada en Fase 1 al crear la vista.
     * El POST /consent ya existía; la GET faltaba porque el ConsentController
     * original solo tenía el método store().
     */
    public function show(): Response
    {
        return Inertia::render('Identity/Consent');
    }

    /**
     * Registra la aceptación del consentimiento informado.
     * Redirige a /profile/complete para continuar el onboarding.
     */
    public function store(Request $request): RedirectResponse
    {
        $this->recordConsent->execute(new RecordConsentDTO(
            userId: $request->user()->id,
        ));

        return redirect()->route('profile.complete');
    }
}
