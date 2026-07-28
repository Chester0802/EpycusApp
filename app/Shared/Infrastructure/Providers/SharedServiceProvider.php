<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Providers;

use App\Shared\Application\TransactionManagerInterface;
use App\Shared\Infrastructure\Persistence\DatabaseTransactionManager;
use Illuminate\Support\ServiceProvider;

final class SharedServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(TransactionManagerInterface::class, DatabaseTransactionManager::class);
    }
}
