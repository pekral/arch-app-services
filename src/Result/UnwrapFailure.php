<?php

declare(strict_types = 1);

namespace Pekral\Arch\Result;

use BackedEnum;
use RuntimeException;

/**
 * Thrown when attempting to unwrap a failed Result or accessing
 * the error of a successful Result.
 */
final class UnwrapFailure extends RuntimeException
{

    /**
     * Create exception for unwrapping a failure Result.
     */
    public static function fromResult(mixed $error): self
    {
        $message = $error instanceof BackedEnum
            ? 'Cannot unwrap a failure Result. Error: ' . $error->value
            : 'Cannot unwrap a failure Result.';

        return new self($message);
    }

    /**
     * Create exception for accessing error on a success Result.
     */
    public static function calledErrorOnSuccess(): self
    {
        return new self('Cannot access error on a success Result.');
    }

}
