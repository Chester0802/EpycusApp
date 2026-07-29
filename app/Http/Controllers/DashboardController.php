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

        return Inertia::render('Dashboard', [
            'progress' => [
                'level' => $this->progress->getLevelFor($userId),
                'phase' => $this->progress->getPhaseFor($userId),
                'totalXp' => $this->progress->getTotalXpFor($userId),
                'currentStreak' => $this->progress->getCurrentStreakFor($userId),
                'coins' => $this->progress->getCoinsFor($userId),
            ],
            // Un solo personaje, fase al azar (no la fase real de
            // progreso) — decisión del usuario tras ver la primera
            // versión. Se resuelve en cada petición, así que recargar la
            // página ya alcanza para verlo cambiar.
            'avatarImage' => $this->avatars->imageForModule($user?->avatar_style, $user?->avatar_gender, 'dashboard'),
        ]);
    }
}
