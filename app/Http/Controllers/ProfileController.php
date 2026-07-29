<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Modules\Identity\Infrastructure\Models\ParticipantModel;
use App\Shared\Domain\Services\AvatarAssetResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    public function __construct(
        private readonly AvatarAssetResolver $avatarResolver,
    ) {}

    public function edit(Request $request): Response
    {
        $user = $request->user();
        $participant = ParticipantModel::where('user_id', $user->id)->first();

        $avatarImage = $this->avatarResolver->imageForModule(
            $user->avatar_style,
            $user->avatar_gender,
            'dashboard',
        );

        return Inertia::render('Profile/Edit', [
            'mustVerifyEmail' => false,
            'status' => session('status'),
            'avatarImage' => $avatarImage,
            'participantCode' => $participant?->participant_code,
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
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit');
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
