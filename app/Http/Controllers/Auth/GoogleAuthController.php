<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Modules\Identity\Infrastructure\Models\UserModel;
use App\Modules\Identity\Infrastructure\Models\ParticipantModel;
use App\Modules\Identity\Infrastructure\Models\UserPreferencesModel;
use App\Modules\Gamification\Infrastructure\Models\UserProgressModel;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

final class GoogleAuthController extends Controller
{
    public function redirect(): RedirectResponse
    {
        $clientId = config('services.google.client_id');
        $redirectUri = config('services.google.redirect');

        if (empty($clientId)) {
            return redirect()->route('login')->with('warning', 'El inicio de sesión con Google estará activo cuando ingreses el CLIENT_ID en el archivo .env del servidor.');
        }

        $query = http_build_query([
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'scope' => 'openid email profile',
            'prompt' => 'select_account',
        ]);

        return redirect()->away('https://accounts.google.com/o/oauth2/v2/auth?' . $query);
    }

    public function callback(Request $request): RedirectResponse
    {
        $code = $request->query('code');
        if (!$code) {
            return redirect()->route('login')->with('error', 'Cancelaste la autenticación con Google.');
        }

        $clientId = config('services.google.client_id');
        $clientSecret = config('services.google.client_secret');
        $redirectUri = config('services.google.redirect');

        try {
            // Intercambiar código por token de acceso
            $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
                'code' => $code,
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'redirect_uri' => $redirectUri,
                'grant_type' => 'authorization_code',
            ]);

            if ($response->failed()) {
                return redirect()->route('login')->with('error', 'Fallo al verificar token de Google Console.');
            }

            $accessToken = $response->json('access_token');

            // Obtener perfil del usuario desde Google
            $userResponse = Http::withToken($accessToken)->get('https://www.googleapis.com/oauth2/v3/userinfo');
            if ($userResponse->failed()) {
                return redirect()->route('login')->with('error', 'No se pudieron obtener los datos de Google.');
            }

            $googleData = $userResponse->json();
            $email = $googleData['email'] ?? null;
            $name = $googleData['name'] ?? 'Estudiante Google';

            if (!$email) {
                return redirect()->route('login')->with('error', 'Google no retornó un correo electrónico válido.');
            }

            $user = UserModel::where('email', $email)->first();

            if (!$user) {
                // Crear usuario seudonimizado para la investigación
                $user = UserModel::create([
                    'name' => $name,
                    'email' => $email,
                    'password' => Hash::make(Str::random(24)),
                    'role' => 'student',
                ]);

                // Generar participante con código seudonimizado
                $pCode = 'P-' . strtoupper(Str::random(6));
                ParticipantModel::create([
                    'user_id' => $user->id,
                    'participant_code' => $pCode,
                ]);

                // Preferencias e inicio de gamificación
                UserPreferencesModel::create([
                    'user_id' => $user->id,
                    'surface_mode' => 'glass',
                ]);

                UserProgressModel::create([
                    'user_id' => $user->id,
                    'current_level' => 1,
                    'current_phase' => 1,
                    'total_xp' => 0,
                    'current_streak' => 0,
                    'coins' => 0,
                ]);
            }

            Auth::login($user, true);

            return redirect()->intended(route('dashboard'));
        } catch (Exception $e) {
            return redirect()->route('login')->with('error', 'Ocurrió un error al procesar el ingreso con Google: ' . $e->getMessage());
        }
    }
}
