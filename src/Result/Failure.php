<?php

declare(strict_types = 1);

namespace Pekral\Arch\Result;

/**
 * Represents a failed operation outcome holding the error value.
 *
 * @template TError
 * @extends \Pekral\Arch\Result\Result<never, TError>
 */
final readonly class Failure extends Result
{

    /**
     * @param TError $error
     */
    public function __construct(private mixed $error)
    {
    }

    public function isSuccess(): bool
    {
        return false;
    }

    /**
     * @throws \Pekral\Arch\Result\UnwrapFailure
     * @return never
     */
    public function unwrap(): mixed
    {
        throw UnwrapFailure::fromResult($this->error);
    }

    /**
     * @return TError
     */
    public function error(): mixed
    {
        return $this->error;
    }

    /**
     * @phpcsSuppress SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
     * @return self<TError>
     */
    public function map(callable $fn): Result
    {
        return $this;
    }

    /**
     * @phpcsSuppress SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
     * @return self<TError>
     */
    public function flatMap(callable $fn): Result
    {
        return $this;
    }

}
