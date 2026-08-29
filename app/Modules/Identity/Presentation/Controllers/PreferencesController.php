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

    /**
     * Obtiene el catálogo de fondos de pantalla fusionando config e imágenes Fondo_*.* de assets/wallpapers/full.
     *
     * @return array<string, array{key: string, file: string, cost: int}>
     */
    private function getWallpaperCatalog(): array
    {
        $items = config('wallpapers.items', []);
        $fullPath = public_path('assets/wallpapers/full');

        if (is_dir($fullPath)) {
            $files = glob($fullPath.'/Fondo_*.*');
            if (is_array($files)) {
                foreach ($files as $filePath) {
                    $filename = basename($filePath);
                    $key = pathinfo($filename, PATHINFO_FILENAME);
                    if (! isset($items[$key])) {
                        $items[$key] = [
                            'key' => $key,
                            'file' => $filename,
                            'cost' => $key === 'Fondo_1' ? 0 : 50,
                        ];
                    }
                }
            }
        }

        // Ordenar numéricamente por Fondo_N
        uksort($items, function ($a, $b) {
            preg_match('/Fondo_(\d+)/i', (string) $a, $mA);
            preg_match('/Fondo_(\d+)/i', (string) $b, $mB);
            $numA = isset($mA[1]) ? (int) $mA[1] : 999;
            $numB = isset($mB[1]) ? (int) $mB[1] : 999;

            return $numA <=> $numB;
        });

        return $items;
    }

    public function edit(): Response
    {
        $userId = (int) Auth::id();
        $catalog = $this->getWallpaperCatalog();

        $userProgress = UserProgressModel::find($userId);
        $userCoins = $userProgress ? $userProgress->coins : 0;

        $unlockedKeys = UserUnlockedWallpaperModel::where('user_id', $userId)
            ->pluck('wallpaper_key')
            ->toArray();
        $unlockedKeys[] = 'Fondo_1';
        $unlockedKeys[] = 'atardecer'; // Retrocompatibilidad

        $prefModel = UserPreferencesModel::where('user_id', $userId)->first();
        $activeKey = $prefModel?->wallpaper_key ?? 'Fondo_1';
        if ($activeKey === 'atardecer') {
            $activeKey = 'Fondo_1';
        }

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
        $catalog = $this->getWallpaperCatalog();

        if (! isset($catalog[$wallpaperKey])) {
            return back()->with('error', 'El fondo de pantalla especificado no existe.');
        }

        $item = $catalog[$wallpaperKey];
        $cost = (int) ($item['cost'] ?? 50);

        // Check if already unlocked
        $alreadyUnlocked = $wallpaperKey === 'Fondo_1'
            || $wallpaperKey === 'atardecer'
            || UserUnlockedWallpaperModel::where('user_id', $userId)
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
        $catalog = $this->getWallpaperCatalog();

        if (! isset($catalog[$wallpaperKey])) {
            return back()->with('error', 'El fondo de pantalla especificado no existe.');
        }

        $isUnlocked = $wallpaperKey === 'Fondo_1'
            || $wallpaperKey === 'atardecer'
            || UserUnlockedWallpaperModel::where('user_id', $userId)
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
