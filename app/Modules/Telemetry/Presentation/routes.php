<?php

declare(strict_types=1);

use App\Modules\Telemetry\Presentation\Controllers\TelemetryController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])->group(function () {
    Route::post('/api/v1/telemetry/batch', [TelemetryController::class, 'storeBatch'])
        ->name('api.telemetry.batch');
});
