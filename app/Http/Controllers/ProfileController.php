<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Modules\Gamification\Domain\Services\LevelCalculator;
use App\Modules\Identity\Infrastructure\Models\ParticipantModel;
use App\Shared\Domain\Contracts\UserProgressReaderInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    public function __construct(
        private UserProgressReaderInterface $progressReader,
    ) {}

    public function edit(Request $request): Response
    {
        $user = $request->user();
        $userId = (int) $user->id;
        $participant = ParticipantModel::where('user_id', $userId)->first();

        // Cargar progreso de gamificación real del usuario
        $level = $this->progressReader->getLevelFor($userId);
        $phase = $this->progressReader->getPhaseFor($userId);
        $totalXp = $this->progressReader->getTotalXpFor($userId);
        $streak = $this->progressReader->getCurrentStreakFor($userId);
        $coins = $this->progressReader->getCoinsFor($userId);

        $levelCalc = app(LevelCalculator::class);
        $accumulated = 0;
        for ($l = 1; $l < $level; $l++) {
            $accumulated += $levelCalc->xpNeededToAdvanceFromLevel($l);
        }
        $currentLevelXp = max(0, $totalXp - $accumulated);
        $nextLevelXpNeeded = $levelCalc->xpNeededToAdvanceFromLevel($level);
        $levelProgressPercent = $nextLevelXpNeeded > 0
            ? min(100, (int) round(($currentLevelXp / $nextLevelXpNeeded) * 100))
            : 100;

        $progressData = [
            'level' => $level,
            'phase' => $phase,
            'totalXp' => $totalXp,
            'currentStreak' => $streak,
            'coins' => $coins,
            'currentLevelXp' => $currentLevelXp,
            'nextLevelXpNeeded' => $nextLevelXpNeeded,
            'levelProgressPercent' => $levelProgressPercent,
        ];

        return Inertia::render('Profile/Edit', [
            'mustVerifyEmail' => false,
            'status' => session('status'),
            'avatarStyle' => $user->avatar_style ?? 'base',
            'avatarGender' => $user->avatar_gender ?? 'm',
            'avatarOptions' => $user->avatar_options,
            'progress' => $progressData,
            'participantCode' => $participant?->participant_code,
            'careers' => config('careers.styles'),
            'cycles' => config('careers.cycles'),
            'institutionTypes' => config('careers.institution_types'),
            'profileData' => [
                'alias' => $user->alias,
                'career' => $user->career,
                'cycle' => $user->cycle,
                'avatarGender' => $user->avatar_gender,
                'institutionType' => $user->institution_type,
            ],
        ]);
    }

    /**
     * Update the user's customized avatar options.
     */
    public function updateAvatarOptions(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'skinColor' => ['nullable', 'string', 'max:10'],
            'head' => ['nullable', 'string', 'max:50'],
            'face' => ['nullable', 'string', 'max:50'],
            'accessories' => ['nullable', 'string', 'max:50'],
            'facialHair' => ['nullable', 'string', 'max:50'],
            'clothingColor' => ['nullable', 'string', 'max:10'],
            'backgroundColor' => ['nullable', 'string', 'max:10'],
        ]);

        $user = $request->user();
        $user->avatar_options = array_filter($validated, fn ($v) => $v !== null);
        $user->save();

        return Redirect::route('profile.edit')->with('success', 'Avatar personalizado guardado.');
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        if (! empty($validated['career'])) {
            $validated['avatar_style'] = \App\Modules\Identity\Domain\ValueObjects\Career::avatarStyle($validated['career']);
        }

        $user->fill($validated);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        return Redirect::route('profile.edit')->with('success', 'Perfil actualizado correctamente.');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
