<?php

declare(strict_types=1);

namespace App\Modules\Identity\Presentation\Controllers;

use App\Modules\Identity\Application\DTOs\CompleteProfileDTO;
use App\Modules\Identity\Application\UseCases\CompleteProfileUseCase;
use App\Modules\Identity\Presentation\Requests\CompleteProfileRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final readonly class ProfileController
{
    public function __construct(private CompleteProfileUseCase $completeProfile) {}

    public function edit(): Response
    {
        return Inertia::render('Identity/CompleteProfile', [
            'careers' => config('careers.styles'),
            'cycles' => config('careers.cycles'),
            'institutionTypes' => config('careers.institution_types'),
        ]);
    }

    public function update(CompleteProfileRequest $request): RedirectResponse
    {
        $dto = new CompleteProfileDTO(
            userId: $request->user()->id,
            career: $request->input('career'),
            avatarStyle: $request->input('avatar_style'),
            avatarGender: $request->input('avatar_gender'),
            cycle: (int) $request->input('cycle'),
            institutionType: $request->input('institution_type'),
        );

        $this->completeProfile->execute($dto);

        return redirect()->route('dashboard');
    }
}
