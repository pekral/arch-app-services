<?php

declare(strict_types = 1);

namespace Pekral\Arch\Transaction;

use Closure;
use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;
use ReflectionMethod;

/**
 * Resolves and executes the #[InTransaction] attribute on action methods.
 *
 * Inspects the __invoke method for the InTransaction attribute and wraps
 * the call in a database transaction when present.
 */
trait TransactionAwareAction
{

    /**
     * @template TResult
     * @param \Closure(): TResult $callback
     * @return TResult
     */
    protected function executeWithTransactionAttribute(Closure $callback): mixed
    {
        $attribute = $this->resolveInTransactionAttribute();

        if ($attribute === null) {
            return $callback();
        }

        $rawAttempts = config('arch.transactions.default_attempts', 1);
        $defaultAttempts = is_int($rawAttempts) ? $rawAttempts : 1;
        $attempts = max(1, $attribute->attempts >= 1 ? $attribute->attempts : $defaultAttempts);

        return DB::connection($attribute->connection)->transaction(
            // phpcs:ignore SlevomatCodingStandard.Functions.UnusedParameter
            static fn (Connection $_db) => $callback(),
            $attempts,
        );
    }

    private function resolveInTransactionAttribute(): ?InTransaction
    {
        $reflection = new ReflectionMethod($this, '__invoke');
        $attributes = $reflection->getAttributes(InTransaction::class);

        if ($attributes === []) {
            return null;
        }

        return $attributes[0]->newInstance();
    }

}
