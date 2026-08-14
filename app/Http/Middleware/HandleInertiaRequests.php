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
     * Agrega el header Vary: X-Inertia en todas las respuestas.
     * Esto instruye a los navegadores y proxies a tratar las respuestas
     * XHR de Inertia (JSON) y las respuestas HTML completas como entradas
     * de caché distintas, evitando que se sirva JSON en lugar de HTML
     * cuando el bfcache se evita o expira.
     */
    public function handle(Request $request, \Closure $next): \Symfony\Component\HttpFoundation\Response
    {
        $response = parent::handle($request, $next);
        $response->headers->set('Vary', 'X-Inertia');
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

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user,
                'hasCompletedEpaPretest' => $hasCompletedEpaPretest,
            ],
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
