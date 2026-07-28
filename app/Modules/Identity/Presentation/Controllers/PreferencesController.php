<?php

declare(strict_types=1);

namespace App\Modules\Identity\Presentation\Controllers;

use App\Modules\Identity\Application\DTOs\UpdatePreferencesDTO;
use App\Modules\Identity\Application\UseCases\UpdatePreferencesUseCase;
use App\Modules\Identity\Presentation\Requests\UpdatePreferencesRequest;
use Illuminate\Http\RedirectResponse;

final readonly class PreferencesController
{
    public function __construct(private UpdatePreferencesUseCase $updatePreferences) {}

    public function update(UpdatePreferencesRequest $request): RedirectResponse
    {
        $this->updatePreferences->execute(new UpdatePreferencesDTO(
            userId: $request->user()->id,
            surfaceMode: $request->input('surface_mode'),
            notificationsEnabled: $request->has('notifications_enabled')
                ? $request->boolean('notifications_enabled')
                : null,
        ));

        return redirect()->back();
    }
}
