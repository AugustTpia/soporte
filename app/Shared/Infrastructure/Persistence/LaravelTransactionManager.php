<?php

namespace App\Shared\Infrastructure\Persistence;

use App\Shared\Application\Contracts\TransactionManager;
use Closure;
use Illuminate\Support\Facades\DB;

final class LaravelTransactionManager implements TransactionManager
{
    /**
     * @template T
     *
     * @param  Closure(): T  $callback
     * @return T
     */
    public function run(Closure $callback): mixed
    {
        return DB::transaction($callback);
    }
}
