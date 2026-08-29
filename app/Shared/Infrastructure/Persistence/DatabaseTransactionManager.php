<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Persistence;

use App\Shared\Application\TransactionManagerInterface;
use Illuminate\Support\Facades\DB;

final class DatabaseTransactionManager implements TransactionManagerInterface
{
    /**
     * @template T
     *
     * @param  \Closure(): T  $operation
     * @return T
     */
    public function run(\Closure $operation): mixed
    {
        return DB::transaction($operation, attempts: 3);
    }
}
