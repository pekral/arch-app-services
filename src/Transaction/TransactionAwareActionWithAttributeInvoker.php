<?php

declare(strict_types = 1);

namespace Pekral\Arch\Transaction;

use Closure;

/**
 * @internal
 */
final class TransactionAwareActionWithAttributeInvoker
{

    use TransactionAwareAction;

    /**
     * @template TResult
     * @param \Closure(): TResult $callback
     * @return TResult
     */
    #[InTransaction]
    public function __invoke(Closure $callback): mixed
    {
        return $this->executeWithTransactionAttribute($callback);
    }

}
