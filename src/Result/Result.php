<?php

declare(strict_types = 1);

namespace Pekral\Arch\Result;

use RuntimeException;

/**
 * @template TValue
 * @template TError
 */
final readonly class Result
{

    /**
     * @param TValue $value
     * @param TError $error
     */
    private function __construct(private mixed $value, private mixed $error, private bool $isSuccess)
    {
    }

    /**
     * @template T
     * @param T $value
     * @return self<T, never>
     * @phpstan-return self<T, never>
     */
    public static function success(mixed $value): self
    {
        // phpcs:ignore SlevomatCodingStandard.Namespaces.FullyQualifiedClassNameInAnnotation.NonFullyQualifiedClassName
        /** @phpstan-var self<T, never> $result */
        $result = new self(value: $value, error: null, isSuccess: true);

        return $result;
    }

    /**
     * @template T
     * @param T $error
     * @return self<never, T>
     * @phpstan-return self<never, T>
     */
    public static function failure(mixed $error): self
    {
        // phpcs:ignore SlevomatCodingStandard.Namespaces.FullyQualifiedClassNameInAnnotation.NonFullyQualifiedClassName
        /** @phpstan-var self<never, T> $result */
        $result = new self(value: null, error: $error, isSuccess: false);

        return $result;
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

        /** @var TValue $value */
        $value = $this->value;

        return $value;
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

        /** @var TError $error */
        $error = $this->error;

        return $error;
    }

    /**
     * @template TNewValue
     * @param callable(TValue): TNewValue $callback
     * @return self<TNewValue, TError>
     */
    public function map(callable $callback): self
    {
        if ($this->isFailure()) {
            /** @var TError $error */
            $error = $this->error;

            return self::failure($error);
        }

        /** @var TValue $value */
        $value = $this->value;

        return self::success($callback($value));
    }

    /**
     * @template TNewValue
     * @param callable(TValue): self<TNewValue, TError> $callback
     * @return self<TNewValue, TError>
     */
    public function flatMap(callable $callback): self
    {
        if ($this->isFailure()) {
            /** @var TError $error */
            $error = $this->error;

            return self::failure($error);
        }

        /** @var TValue $value */
        $value = $this->value;

        return $callback($value);
    }

    /**
     * @param callable(TError): void $callback
     * @return self<TValue, TError>
     */
    public function onFailure(callable $callback): self
    {
        if ($this->isFailure()) {
            /** @var TError $error */
            $error = $this->error;

            $callback($error);
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
            /** @var TValue $value */
            $value = $this->value;

            $callback($value);
        }

        return $this;
    }

}
