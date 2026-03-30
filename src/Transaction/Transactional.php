<?php

declare(strict_types = 1);

namespace Pekral\Arch\Transaction;

use Closure;
use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;

/**
 * Provides explicit database transaction support for Actions.
 *
 * Wraps business logic in a database transaction to ensure atomicity
 * when multiple write operations must succeed or fail together.
 */
trait Transactional
{

    /**
     * @template TResult
     * @param \Closure(): TResult $callback
     * @return TResult
     */
    protected function transaction(Closure $callback, ?int $attempts = null, ?string $connection = null): mixed
    {
        $rawAttempts = config('arch.transactions.default_attempts', 1);
        $resolvedAttempts = max(1, $attempts ?? (is_int($rawAttempts) ? $rawAttempts : 1));

        return DB::connection($connection)->transaction(
            // phpcs:ignore SlevomatCodingStandard.Functions.UnusedParameter
            fn (Connection $_db) => $callback(),
            $resolvedAttempts,
        );
    }

}
