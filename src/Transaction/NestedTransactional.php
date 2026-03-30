<?php

declare(strict_types = 1);

namespace Pekral\Arch\Transaction;

use Closure;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Throwable;

/**
 * Provides savepoint-based nested transaction support for Actions.
 *
 * Allows partial rollback within a larger transaction by creating named
 * savepoints that can be independently rolled back on failure.
 */
trait NestedTransactional
{

    private const string SAVEPOINT_NAME_PATTERN = '/^[a-zA-Z_]\w*$/';

    /**
     * @template TResult
     * @param \Closure(): TResult $callback
     * @return TResult
     * @throws \Throwable
     */
    protected function savepoint(string $name, Closure $callback, ?string $connection = null): mixed
    {
        if (preg_match(self::SAVEPOINT_NAME_PATTERN, $name) !== 1) {
            throw new InvalidArgumentException(
                sprintf('Savepoint name "%s" contains invalid characters. Only alphanumeric characters and underscores are allowed.', $name),
            );
        }

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
