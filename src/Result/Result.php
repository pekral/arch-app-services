<?php

declare(strict_types = 1);

namespace Pekral\Arch\Result;

use RuntimeException;

/**
 * Result pattern implementation for explicit error handling without exceptions.
 *
 * @template-covariant TValue
 * @template-covariant TError
 */
final readonly class Result
{

    /**
     * @param TValue $value @param TError $error
     */
    private function __construct(private mixed $value, private mixed $error, private bool $isSuccess)
    {
    }

    /**
     * @template T
     * @param T $value
     * @return self<T, null>
     */
    public static function success(mixed $value): self
    {
        /** @phpstan-ignore return.type */
        return new self(value: $value, error: null, isSuccess: true);
    }

    /**
     * @template T
     * @param T $error
     * @return self<null, T>
     */
    public static function failure(mixed $error): self
    {
        /** @phpstan-ignore return.type */
        return new self(value: null, error: $error, isSuccess: false);
    }

    public function isSuccess(): bool
    {
        return $this->isSuccess;
    }

    public function isFailure(): bool
    {
        return !$this->isSuccess;
    }

    /**
     * @return TValue
     * @throws \RuntimeException
     */
    public function value(): mixed
    {
        if (!$this->isSuccess) {
            throw new RuntimeException('Cannot get value from failed result');
        }

        return $this->value;
    }

    /**
     * @return TError
     * @throws \RuntimeException
     */
    public function error(): mixed
    {
        if ($this->isSuccess) {
            throw new RuntimeException('Cannot get error from successful result');
        }

        return $this->error;
    }

    /**
     * @template TNewValue
     * @param callable(TValue): TNewValue $callback
     * @return self<TNewValue, TError>
     */
    public function map(callable $callback): self
    {
        if ($this->isFailure()) {
            /** @phpstan-ignore return.type */
            return new self(value: null, error: $this->error, isSuccess: false);
        }

        /** @phpstan-ignore return.type */
        return new self(value: $callback($this->value), error: null, isSuccess: true);
    }

    /**
     * @template TNewValue
     * @template TNewError
     * @param callable(TValue): self<TNewValue, TNewError> $callback
     * @return self<TNewValue, TError|TNewError>
     */
    public function flatMap(callable $callback): self
    {
        if ($this->isFailure()) {
            /** @phpstan-ignore return.type */
            return new self(value: null, error: $this->error, isSuccess: false);
        }

        return $callback($this->value);
    }

    /**
     * @param callable(TError): void $callback
     * @return self<TValue, TError>
     */
    public function onFailure(callable $callback): self
    {
        if ($this->isFailure()) {
            $callback($this->error);
        }

        return $this;
    }

    /**
     * @param callable(TValue): void $callback
     * @return self<TValue, TError>
     */
    public function onSuccess(callable $callback): self
    {
        if ($this->isSuccess) {
            $callback($this->value);
        }

        return $this;
    }

    /**
     * @template TDefault
     * @param TDefault $default
     * @return TValue|TDefault
     */
    public function valueOr(mixed $default): mixed
    {
        if ($this->isFailure()) {
            return $default;
        }

        return $this->value;
    }

    /**
     * @template TNewError
     * @param callable(TError): TNewError $callback
     * @return self<TValue, TNewError>
     */
    public function mapError(callable $callback): self
    {
        if ($this->isSuccess()) {
            /** @phpstan-ignore return.type */
            return new self(value: $this->value, error: null, isSuccess: true);
        }

        /** @phpstan-ignore return.type */
        return new self(value: null, error: $callback($this->error), isSuccess: false);
    }

}
