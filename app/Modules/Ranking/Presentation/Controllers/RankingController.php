<?php

declare(strict_types=1);

namespace App\Modules\Ranking\Presentation\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Ranking\Application\UseCases\GetGlobalRankingUseCase;
use App\Modules\Ranking\Application\UseCases\GetOwnPositionUseCase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

final class RankingController extends Controller
{
    public function __construct(
        private readonly GetGlobalRankingUseCase $getGlobalRanking,
        private readonly GetOwnPositionUseCase $getOwnPosition,
    ) {}

    public function index(Request $request): Response
    {
        $userId = (int) Auth::id();
        $user = Auth::user();

        // Caching 7 minutos por docs/01-MODULOS.md §9.5
        $ranking = Cache::remember('epycus:global_ranking', 420, function () {
            return $this->getGlobalRanking->execute();
        });

        $ownPosition = $this->getOwnPosition->execute($ranking, $userId);

        return Inertia::render('Ranking/Index', [
            'ranking' => $ranking,
            'ownPosition' => $ownPosition,
            'avatarStyle' => $user?->avatar_style ?? 'base',
            'avatarGender' => $user?->avatar_gender ?? 'm',
            'avatarOptions' => $user?->avatar_options,
        ]);
    }
}
