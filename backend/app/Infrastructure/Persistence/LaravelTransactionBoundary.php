<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use Fleet\Application\TransactionBoundary;
use Illuminate\Support\Facades\DB;

final class LaravelTransactionBoundary implements TransactionBoundary
{
    public function run(callable $callback): mixed
    {
        return DB::transaction($callback);
    }
}
