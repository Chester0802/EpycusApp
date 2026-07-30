<?php

declare(strict_types=1);

namespace App\Modules\Villains\Presentation\Controllers;

use App\Modules\Villains\Application\UseCases\GetCurrentVillainUseCase;
use Illuminate\Routing\Controller;
use Inertia\Response;

final class VillainController extends Controller
{
    public function __construct(
        private GetCurrentVillainUseCase $getCurrentVillain,
    ) {}

    public function index(): Response
    {
        $userId = auth()->id();
        $currentVillain = $this->getCurrentVillain->execute($userId);

        return inertia('Villains/Index', [
            'villain' => $currentVillain,
        ]);
    }
}
