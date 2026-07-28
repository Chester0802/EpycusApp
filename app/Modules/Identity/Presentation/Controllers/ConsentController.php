<?php

declare(strict_types=1);

namespace App\Modules\Identity\Presentation\Controllers;

use App\Modules\Identity\Application\DTOs\RecordConsentDTO;
use App\Modules\Identity\Application\UseCases\RecordConsentUseCase;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final readonly class ConsentController
{
    public function __construct(private RecordConsentUseCase $recordConsent) {}

    public function store(Request $request): RedirectResponse
    {
        $this->recordConsent->execute(new RecordConsentDTO(
            userId: $request->user()->id,
        ));

        return redirect()->back();
    }
}
