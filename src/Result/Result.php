<?php

declare(strict_types = 1);

namespace Pekral\Arch\Result;

/**
 * Type-safe representation of an operation outcome — either success or failure.
 *
 * Provides a functional alternative to exception-based error handling,
 * making expected failures explicit in method signatures and enabling
 * composable transformations via map/flatMap.
 *
 * @template-covariant TSuccess
 * @template-covariant TError
 */
abstract readonly class Result
{

    abstract public function isSuccess(): bool;

    /**
     * Return the success value or throw if this is a failure.
     *
     * @return TSuccess
     * @throws \Pekral\Arch\Result\UnwrapFailure
     */
    abstract public function unwrap(): mixed;

    /**
     * Return the success value or the provided default.
     *
     * @template TDefault
     * @param TDefault $default
     * @return TSuccess|TDefault
     */
    abstract public function unwrapOr(mixed $default): mixed;

    /**
     * Return the error value or throw if this is a success.
     *
     * @return TError
     * @throws \Pekral\Arch\Result\UnwrapFailure
     */
    abstract public function error(): mixed;

    /**
     * Transform the success value, leaving failures untouched.
     *
     * @template TNew
     * @param callable(TSuccess): TNew $fn
     * @return self<TNew, TError>
     */
    abstract public function map(callable $fn): self;

    /**
     * Chain a dependent operation that itself returns a Result.
     *
     * @template TNew
     * @param callable(TSuccess): self<TNew, mixed> $fn
     * @return self<TNew, mixed>
     */
    abstract public function flatMap(callable $fn): self;

    /**
     * @template T
     * @param T $value
     * @return self<T, never>
     */
    public static function success(mixed $value): self
    {
        return new Success($value);
    }

    /**
     * @template E
     * @param E $error
     * @return self<never, E>
     */
    public static function failure(mixed $error): self
    {
        return new Failure($error);
    }

    public function isFailure(): bool
    {
        return !$this->isSuccess();
    }

}
