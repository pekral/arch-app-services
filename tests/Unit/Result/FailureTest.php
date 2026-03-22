<?php

declare(strict_types = 1);

use Pekral\Arch\Result\Failure;
use Pekral\Arch\Result\Result;
use Pekral\Arch\Result\UnwrapFailure;

test('failure is instance of Result', function (): void {
    $failure = new Failure('error');

    expect($failure)->toBeInstanceOf(Result::class);
});

test('failure isSuccess returns false', function (): void {
    $failure = new Failure('error');

    expect($failure->isSuccess())->toBeFalse();
});

test('failure isFailure returns true', function (): void {
    $failure = new Failure('error');

    expect($failure->isFailure())->toBeTrue();
});

test('failure unwrap throws', function (): void {
    $failure = new Failure('some error');

    $failure->unwrap();
})->throws(UnwrapFailure::class, 'Cannot unwrap a failure Result.');

test('failure unwrapOr returns the default', function (): void {
    $failure = new Failure('error');

    expect($failure->unwrapOr('fallback'))->toBe('fallback');
});

test('failure error returns the error', function (): void {
    $failure = new Failure('my error');

    expect($failure->error())->toBe('my error');
});

test('failure map returns itself without transformation', function (): void {
    $failure = new Failure('error');

    $mapped = $failure->map(fn (mixed $value): string => 'should not execute ' . $value);

    expect($mapped)->toBe($failure)
        ->and($mapped->isFailure())->toBeTrue()
        ->and($mapped->error())->toBe('error');
});

test('failure flatMap returns itself without calling callback', function (): void {
    $failure = new Failure('error');

    $chained = $failure->flatMap(fn (mixed $value): Result => Result::success('should not execute ' . $value));

    expect($chained)->toBe($failure)
        ->and($chained->isFailure())->toBeTrue()
        ->and($chained->error())->toBe('error');
});
