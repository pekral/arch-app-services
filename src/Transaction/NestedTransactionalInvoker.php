<?php

declare(strict_types = 1);

namespace Pekral\Arch\Transaction;

use Closure;

/**
 * @internal
 */
final class NestedTransactionalInvoker
{
    use NestedTransactional;

    /**
     * @template TResult
     * @param \Closure(): TResult $callback
     * @return TResult
     */
    public function runSavepoint(string $name, Closure $callback, ?string $connection = null): mixed
    {
        return $this->savepoint($name, $callback, $connection);
    }
}

