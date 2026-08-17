<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;


final class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        if (config('app.env') === 'production' || str_contains(request()->header('Host', ''), 'epycus.es')) {
            URL::forceScheme('https');
        }
    }

}
