<?php

namespace App\Http\Controllers;

use App\Shared\Domain\Contracts\UserProgressReaderInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(private UserProgressReaderInterface $progress) {}

    public function index(Request $request): Response
    {
        $userId = (int) Auth::id();

        return Inertia::render('Dashboard', [
            'progress' => [
                'level' => $this->progress->getLevelFor($userId),
                'phase' => $this->progress->getPhaseFor($userId),
                'totalXp' => $this->progress->getTotalXpFor($userId),
                'currentStreak' => $this->progress->getCurrentStreakFor($userId),
                'coins' => $this->progress->getCoinsFor($userId),
            ],
        ]);
    }
}
