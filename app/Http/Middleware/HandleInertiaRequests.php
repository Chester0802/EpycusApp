<?php

namespace App\Http\Middleware;

use App\Modules\Identity\Domain\Contracts\UserPreferencesRepositoryInterface;
use App\Modules\Identity\Domain\ValueObjects\UserId;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    public function __construct(private readonly UserPreferencesRepositoryInterface $preferences) {}

    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Agrega headers para evitar que los navegadores móviles guarden en caché
     * las respuestas JSON de Inertia y las muestren como texto plano al restaurar
     * pestañas en segundo plano.
     */
    public function handle(Request $request, \Closure $next): \Symfony\Component\HttpFoundation\Response
    {
        $response = parent::handle($request, $next);
        $response->headers->set('Vary', 'X-Inertia');

        // Si es una solicitud interna de Inertia (XHR JSON), prohibir el almacenamiento en caché
        if ($request->header('X-Inertia')) {
            $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0, post-check=0, pre-check=0');
            $response->headers->set('Pragma', 'no-cache');
            $response->headers->set('Expires', 'Sat, 01 Jan 2000 00:00:00 GMT');
        }

        return $response;
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();
        $preferences = $user ? $this->preferences->findByUserId(new UserId($user->id)) : null;
        $hasCompletedEpaPretest = $user ? DB::table('epa_responses')->where('user_id', $user->id)->where('phase', 'pretest')->exists() : true;

        $activePomodoro = null;
        if ($user) {
            /** @var \App\Modules\Pomodoro\Infrastructure\Models\PomodoroSessionModel|null $session */
            $session = \App\Modules\Pomodoro\Infrastructure\Models\PomodoroSessionModel::where('user_id', $user->id)
                ->whereIn('status', ['running', 'paused'])
                ->latest('id')
                ->first();

            if ($session) {
                $missionTitle = null;
                if ($session->mission_id) {
                    $missionTitle = DB::table('missions')->where('id', $session->mission_id)->value('title');
                }

                $activePomodoro = [
                    'id' => $session->id,
                    'planned_minutes' => $session->planned_minutes,
                    'mission_id' => $session->mission_id,
                    'mission_title' => $missionTitle,
                    'started_at' => $session->started_at->setTimezone('America/Lima')->toIso8601String(),
                    'paused_at' => $session->paused_at?->setTimezone('America/Lima')->toIso8601String(),
                    'total_paused_seconds' => $session->total_paused_seconds,
                    'status' => $session->status,
                    'server_now' => \Carbon\CarbonImmutable::now('America/Lima')->toIso8601String(),
                ];
            }
        }

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user,
                'hasCompletedEpaPretest' => $hasCompletedEpaPretest,
            ],
            'activePomodoro' => $activePomodoro,
            'preferences' => $preferences ? [
                'surfaceMode' => $preferences->surfaceMode()->value(),
                'wallpaperKey' => $preferences->wallpaperKey(),
                'notificationsEnabled' => $preferences->notificationsEnabled(),
            ] : null,
            'flash' => [
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
                'warning' => $request->session()->get('warning'),
            ],
        ];
    }
}
