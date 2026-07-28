<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Persistence;

use App\Shared\Application\TransactionManagerInterface;
use Illuminate\Support\Facades\DB;

final class DatabaseTransactionManager implements TransactionManagerInterface
{
    public function run(callable $operation): mixed
    {
        return DB::transaction($operation, attempts: 3);
    }
}
