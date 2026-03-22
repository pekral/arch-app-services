<?php

declare(strict_types = 1);

namespace Pekral\Arch\Result;

/**
 * Represents a successful operation outcome holding the result value.
 *
 * @template TSuccess
 * @extends \Pekral\Arch\Result\Result<TSuccess, never>
 */
final readonly class Success extends Result
{

    /**
     * @param TSuccess $value
     */
    public function __construct(private mixed $value)
    {
    }

    public function isSuccess(): bool
    {
        return true;
    }

    /**
     * @return TSuccess
     */
    public function unwrap(): mixed
    {
        return $this->value;
    }

    /**
     * @phpcsSuppress SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
     * @return TSuccess
     */
    public function unwrapOr(mixed $default): mixed
    {
        return $this->value;
    }

    /**
     * @throws \Pekral\Arch\Result\UnwrapFailure
     * @return never
     */
    public function error(): mixed
    {
        throw UnwrapFailure::calledErrorOnSuccess();
    }

    /**
     * @template TNew
     * @param callable(TSuccess): TNew $fn
     * @return \Pekral\Arch\Result\Result<TNew, never>
     */
    public function map(callable $fn): Result
    {
        return Result::success($fn($this->value));
    }

    /**
     * @template TNew
     * @param callable(TSuccess): \Pekral\Arch\Result\Result<TNew, never> $fn
     * @return \Pekral\Arch\Result\Result<TNew, never>
     */
    public function flatMap(callable $fn): Result
    {
        return $fn($this->value);
    }

}
