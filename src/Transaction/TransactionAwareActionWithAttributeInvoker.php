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

    #[InTransaction]
    public function __invoke(Closure $callback): mixed
    {
        return $this->executeWithTransactionAttribute($callback);
    }
}

