<?php

namespace App\Http\Controllers;

use App\Shared\Domain\Contracts\UserProgressReaderInterface;
use App\Shared\Domain\Services\AvatarAssetResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(
        private UserProgressReaderInterface $progress,
        private AvatarAssetResolver $avatars,
    ) {}

    public function index(Request $request): Response
    {
        $userId = (int) Auth::id();
        $user = Auth::user();
        $phase = $this->progress->getPhaseFor($userId);

        return Inertia::render('Dashboard', [
            'progress' => [
                'level' => $this->progress->getLevelFor($userId),
                'phase' => $phase,
                'totalXp' => $this->progress->getTotalXpFor($userId),
                'currentStreak' => $this->progress->getCurrentStreakFor($userId),
                'coins' => $this->progress->getCoinsFor($userId),
            ],
            // El orden se baraja acá, en el servidor: cada carga de página
            // (recarga completa) es una petición nueva, así que ya sale
            // barajado distinto cada vez sin necesitar nada en el cliente.
            'avatarImages' => $this->avatars->imagesFor(
                $user?->avatar_style,
                $user?->avatar_gender,
                $phase,
            ),
        ]);
    }
}
