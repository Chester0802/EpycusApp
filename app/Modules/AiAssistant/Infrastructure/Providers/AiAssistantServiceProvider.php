<?php

declare(strict_types=1);

namespace App\Modules\AiAssistant\Infrastructure\Providers;

use App\Modules\AiAssistant\Presentation\Controllers\AiAssistantController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

final class AiAssistantServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Migrations');
        $this->registerRoutes();
    }

    private function registerRoutes(): void
    {
        Route::middleware(['web', 'auth'])->group(function () {
            Route::get('/ai-assistant', [AiAssistantController::class, 'index'])->name('ai-assistant.index');
            Route::get('/ai-assistant/conversations/{id}', [AiAssistantController::class, 'getConversationMessages'])->name('ai-assistant.conversation');
            Route::post('/ai-assistant/consult', [AiAssistantController::class, 'consult'])->name('ai-assistant.consult');
            Route::post('/ai-assistant/rate', [AiAssistantController::class, 'rate'])->name('ai-assistant.rate');
        });
    }
}
