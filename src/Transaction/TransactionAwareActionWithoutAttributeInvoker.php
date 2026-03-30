<?php

declare(strict_types = 1);

namespace Pekral\Arch\Transaction;

use Closure;

/**
 * @internal
 */
final class TransactionAwareActionWithoutAttributeInvoker
{

    use TransactionAwareAction;

    /**
     * @template TResult
     * @param \Closure(): TResult $callback
     * @return TResult
     */
    public function __invoke(Closure $callback): mixed
    {
        return $this->executeWithTransactionAttribute($callback);
    }

}
