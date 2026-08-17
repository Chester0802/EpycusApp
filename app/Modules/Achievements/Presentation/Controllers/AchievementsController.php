<?php

declare(strict_types=1);

namespace App\Modules\Achievements\Presentation\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Achievements\Application\UseCases\GetUserAchievementsUseCase;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

final class AchievementsController extends Controller
{
    public function __construct(
        private readonly GetUserAchievementsUseCase $getUserAchievements,
    ) {}

    public function index(): Response
    {
        $userId = (int) Auth::id();
        $user = Auth::user();

        $data = $this->getUserAchievements->execute($userId);

        return Inertia::render('Achievements/Index', [
            'summary' => [
                'total' => $data['total_count'],
                'unlocked' => $data['unlocked_count'],
                'percent' => $data['progress_percent'],
                'total_xp_earned' => $data['total_xp_earned'],
            ],
            'achievements' => $data['achievements'],
            'avatarStyle' => $user?->avatar_style ?? 'base',
            'avatarGender' => $user?->avatar_gender ?? 'm',
        ]);
    }
}
