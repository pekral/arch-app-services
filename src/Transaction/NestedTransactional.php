<?php

declare(strict_types = 1);

namespace Pekral\Arch\Transaction;

use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Provides savepoint-based nested transaction support for Actions.
 *
 * Allows partial rollback within a larger transaction by creating named
 * savepoints that can be independently rolled back on failure.
 */
trait NestedTransactional
{

    /**
     * @template TResult
     * @param callable(): TResult $callback
     * @return TResult
     * @throws \Throwable
     */
    protected function savepoint(string $name, callable $callback, ?string $connection = null): mixed
    {
        $db = DB::connection($connection);

        $db->statement(sprintf('SAVEPOINT %s', $name));

        try {
            $result = $callback();

            $db->statement(sprintf('RELEASE SAVEPOINT %s', $name));

            return $result;
        } catch (Throwable $exception) {
            $db->statement(sprintf('ROLLBACK TO SAVEPOINT %s', $name));

            throw $exception;
        }
    }

}
