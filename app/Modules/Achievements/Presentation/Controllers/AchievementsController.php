<?php

declare(strict_types=1);

namespace App\Modules\Achievements\Presentation\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Achievements\Application\UseCases\GetUserAchievementsUseCase;
use App\Shared\Domain\Services\AvatarAssetResolver;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

final class AchievementsController extends Controller
{
    public function __construct(
        private readonly GetUserAchievementsUseCase $getUserAchievements,
        private readonly AvatarAssetResolver $avatars,
    ) {}

    public function index(): Response
    {
        $userId = (int) Auth::id();
        $user = Auth::user();

        $data = $this->getUserAchievements->execute($userId);
        $avatarImage = $this->avatars->imageForModule($user?->avatar_style, $user?->avatar_gender, 'dashboard');

        return Inertia::render('Achievements/Index', [
            'summary' => [
                'total' => $data['total_count'],
                'unlocked' => $data['unlocked_count'],
                'percent' => $data['progress_percent'],
            ],
            'achievements' => $data['achievements'],
            'avatarImage' => $avatarImage,
        ]);
    }
}
