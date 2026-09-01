<?php

declare(strict_types=1);

namespace Fleet\Application;

interface TransactionBoundary
{
    /**
     * @template T
     *
     * @param  callable(): T  $callback
     * @return T
     */
    public function run(callable $callback): mixed;
}
