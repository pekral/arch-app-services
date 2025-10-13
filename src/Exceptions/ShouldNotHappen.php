<?php

declare(strict_types = 1);

namespace Pekral\Arch\Exceptions;

use RuntimeException;

/**
 * Exception thrown when an unexpected situation occurs in the application.
 *
 * This exception indicates a programming error or violation of business logic
 * that should never happen under normal circumstances. It suggests that there
 * is a bug in the code or invalid state that needs to be investigated.
 */
final class ShouldNotHappen extends RuntimeException
{

    /**
     * Create a new ShouldNotHappen exception with the given reason.
     *
     * @param string $reason Description of why this situation should not happen
     */
    public static function because(string $reason): self
    {
        return new self($reason);
    }

}
