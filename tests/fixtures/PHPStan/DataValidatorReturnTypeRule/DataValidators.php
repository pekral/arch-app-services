<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests\Fixtures\PHPStan\DataValidatorReturnTypeRule\Validators;

use InvalidArgumentException;

// Valid: bool and void public methods, a constructor and non-public helpers with any return type.
final readonly class OrderDataValidator
{

    public function __construct(private int $limit)
    {
    }

    public function isValid(int $amount): bool
    {
        return $this->normalize($amount) <= $this->limit;
    }

    public function assertValid(int $amount): void
    {
        // Any exception is allowed; only HTTP response exceptions are reported, by NoHttpExceptionsInDataValidatorsRule.
        throw_unless($this->isValid($amount), InvalidArgumentException::class);
    }

    /**
     * @return array<string, int>
     */
    protected function context(): array
    {
        return ['limit' => $this->limit];
    }

    private function normalize(int $amount): int
    {
        return abs($amount);
    }

}

// Intentionally declares disallowed return types to trigger DataValidatorReturnTypeRule.
final class InvoiceDataValidator
{

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function validate(array $data): array
    {
        return $data;
    }

    public function isPaid(?bool $paid): ?bool
    {
        return $paid;
    }

    public function isDue(): true
    {
        return true;
    }

    /**
     * @phpstan-ignore missingType.return
     */
    public function check(bool $condition)
    {
        return $condition;
    }

    public static function make(): self
    {
        return new self();
    }

}
