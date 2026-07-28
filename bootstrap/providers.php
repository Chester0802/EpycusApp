<?php

use App\Modules\Habits\Infrastructure\Providers\HabitsServiceProvider;
use App\Modules\Identity\Infrastructure\Providers\IdentityServiceProvider;
use App\Modules\Telemetry\Infrastructure\Providers\TelemetryServiceProvider;
use App\Providers\AppServiceProvider;
use App\Shared\Infrastructure\Providers\SharedServiceProvider;

return [
    AppServiceProvider::class,
    SharedServiceProvider::class,
    IdentityServiceProvider::class,
    TelemetryServiceProvider::class,
    HabitsServiceProvider::class,
];
