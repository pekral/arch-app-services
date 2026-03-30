<?php

declare(strict_types = 1);

namespace Pekral\Arch\Transaction;

use Attribute;

/**
 * Marks an action's __invoke method to automatically run within a database transaction.
 *
 * When applied, the TransactionAwareAction trait wraps the method execution
 * in a database transaction with configurable retry attempts and connection.
 */
#[Attribute(Attribute::TARGET_METHOD)]
final readonly class InTransaction
{

    public function __construct(public int $attempts = 1, public ?string $connection = null)
    {
    }

}
