<?php

namespace App\Http\Middleware;

use App\Modules\Identity\Domain\Contracts\UserPreferencesRepositoryInterface;
use App\Modules\Identity\Domain\ValueObjects\UserId;
use Illuminate\Http\Request;
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
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();
        $preferences = $user ? $this->preferences->findByUserId(new UserId($user->id)) : null;

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user,
            ],
            'preferences' => $preferences ? [
                'surfaceMode' => $preferences->surfaceMode()->value(),
                'notificationsEnabled' => $preferences->notificationsEnabled(),
            ] : null,
            'flash' => [
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
            ],
        ];
    }
}
