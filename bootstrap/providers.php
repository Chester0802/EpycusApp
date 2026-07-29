<?php

use App\Modules\Gamification\Infrastructure\Providers\GamificationServiceProvider;
use App\Modules\Habits\Infrastructure\Providers\HabitsServiceProvider;
use App\Modules\Identity\Infrastructure\Providers\IdentityServiceProvider;
use App\Modules\Pomodoro\Infrastructure\Providers\PomodoroServiceProvider;
use App\Modules\Telemetry\Infrastructure\Providers\TelemetryServiceProvider;
use App\Providers\AppServiceProvider;
use App\Shared\Infrastructure\Providers\SharedServiceProvider;

return [
    AppServiceProvider::class,
    SharedServiceProvider::class,
    IdentityServiceProvider::class,
    TelemetryServiceProvider::class,
    HabitsServiceProvider::class,
    PomodoroServiceProvider::class,
    // Gamification se registra después de Habits y Pomodoro: su
    // ServiceProvider escucha los eventos de dominio de ambos en boot().
    // El orden de arranque no es estrictamente obligatorio para que
    // Event::listen() funcione (los listeners se resuelven perezosamente
    // recién cuando el evento se dispara), pero se deja así por claridad.
    GamificationServiceProvider::class,
];
