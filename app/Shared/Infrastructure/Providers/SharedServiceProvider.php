<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Providers;

use App\Shared\Application\TransactionManagerInterface;
use App\Shared\Exceptions\Handler as DomainExceptionHandler;
use App\Shared\Infrastructure\Persistence\DatabaseTransactionManager;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Support\ServiceProvider;

final class SharedServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(TransactionManagerInterface::class, DatabaseTransactionManager::class);
        $this->app->singleton(ExceptionHandler::class, DomainExceptionHandler::class);
    }
}
