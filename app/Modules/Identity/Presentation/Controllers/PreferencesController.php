<?php

declare(strict_types=1);

namespace App\Modules\Identity\Presentation\Controllers;

use App\Modules\Gamification\Infrastructure\Models\UserProgressModel;
use App\Modules\Identity\Application\DTOs\UpdatePreferencesDTO;
use App\Modules\Identity\Application\UseCases\UpdatePreferencesUseCase;
use App\Modules\Identity\Infrastructure\Models\UserPreferencesModel;
use App\Modules\Identity\Infrastructure\Models\UserUnlockedWallpaperModel;
use App\Modules\Identity\Presentation\Requests\UpdatePreferencesRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

final readonly class PreferencesController
{
    public function __construct(private UpdatePreferencesUseCase $updatePreferences) {}

    public function edit(): Response
    {
        $userId = (int) Auth::id();
        $catalog = config('wallpapers.items', []);

        $userProgress = UserProgressModel::find($userId);
        $userCoins = $userProgress ? $userProgress->coins : 0;

        $unlockedKeys = UserUnlockedWallpaperModel::where('user_id', $userId)
            ->pluck('wallpaper_key')
            ->toArray();
        $unlockedKeys[] = 'atardecer'; // Default is always unlocked

        $prefModel = UserPreferencesModel::where('user_id', $userId)->first();
        $activeKey = $prefModel?->wallpaper_key ?? 'atardecer';

        return Inertia::render('Settings/Index', [
            'wallpapers' => array_values($catalog),
            'unlockedWallpapers' => array_values(array_unique($unlockedKeys)),
            'activeWallpaperKey' => $activeKey,
            'userCoins' => $userCoins,
        ]);
    }

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

    public function unlockWallpaper(Request $request): RedirectResponse
    {
        $userId = (int) Auth::id();
        $wallpaperKey = (string) $request->input('wallpaper_key');
        $catalog = config('wallpapers.items', []);

        if (! isset($catalog[$wallpaperKey])) {
            return back()->with('error', 'El fondo de pantalla especificado no existe.');
        }

        $item = $catalog[$wallpaperKey];
        $cost = (int) ($item['cost'] ?? 50);

        // Check if already unlocked
        $alreadyUnlocked = $wallpaperKey === 'atardecer' || UserUnlockedWallpaperModel::where('user_id', $userId)
            ->where('wallpaper_key', $wallpaperKey)
            ->exists();

        if ($alreadyUnlocked) {
            return back()->with('info', 'Este fondo de pantalla ya está desbloqueado.');
        }

        // Anti-Cheat / Server-side Coin Validation
        $userProgress = UserProgressModel::find($userId);
        if (! $userProgress || $userProgress->coins < $cost) {
            return back()->with('error', "Monedas insuficientes. Necesitas {$cost} monedas para desbloquear este fondo.");
        }

        DB::transaction(function () use ($userId, $wallpaperKey, $cost) {
            // Deduct coins
            UserProgressModel::where('user_id', $userId)->decrement('coins', $cost);

            // Record unlock
            UserUnlockedWallpaperModel::create([
                'user_id' => $userId,
                'wallpaper_key' => $wallpaperKey,
            ]);

            // Set as active wallpaper
            UserPreferencesModel::updateOrCreate(
                ['user_id' => $userId],
                ['wallpaper_key' => $wallpaperKey]
            );
        });

        return back()->with('success', '¡Fondo desbloqueado y seleccionado con éxito!');
    }

    public function selectWallpaper(Request $request): RedirectResponse
    {
        $userId = (int) Auth::id();
        $wallpaperKey = (string) $request->input('wallpaper_key');
        $catalog = config('wallpapers.items', []);

        if (! isset($catalog[$wallpaperKey])) {
            return back()->with('error', 'El fondo de pantalla especificado no existe.');
        }

        $isUnlocked = $wallpaperKey === 'atardecer' || UserUnlockedWallpaperModel::where('user_id', $userId)
            ->where('wallpaper_key', $wallpaperKey)
            ->exists();

        if (! $isUnlocked) {
            return back()->with('error', 'Debes desbloquear este fondo de pantalla antes de seleccionarlo.');
        }

        UserPreferencesModel::updateOrCreate(
            ['user_id' => $userId],
            ['wallpaper_key' => $wallpaperKey]
        );

        return back()->with('success', 'Fondo de pantalla actualizado.');
    }
}

