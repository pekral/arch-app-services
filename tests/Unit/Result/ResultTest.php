<?php

declare(strict_types = 1);

use Pekral\Arch\Result\Result;
use Pekral\Arch\Result\UnwrapFailure;

test('success creates a success result', function (): void {
    $result = Result::success('value');

    expect($result->isSuccess())->toBeTrue()
        ->and($result->isFailure())->toBeFalse();
});

test('failure creates a failure result', function (): void {
    $result = Result::failure('error');

    expect($result->isFailure())->toBeTrue()
        ->and($result->isSuccess())->toBeFalse();
});

test('unwrap returns the value on success', function (): void {
    $result = Result::success('hello');

    expect($result->unwrap())->toBe('hello');
});

test('unwrap throws on failure', function (): void {
    $result = Result::failure('some error');

    $result->unwrap();
})->throws(UnwrapFailure::class, 'Cannot unwrap a failure Result.');

test('unwrap throws with enum message on failure with backed enum', function (): void {
    $result = Result::failure(TestResultError::VALIDATION);

    $result->unwrap();
})->throws(UnwrapFailure::class, 'Cannot unwrap a failure Result. Error: validation_failed');

test('unwrapOr returns the value on success', function (): void {
    $result = Result::success('real');

    expect($result->unwrapOr('default'))->toBe('real');
});

test('unwrapOr returns the default on failure', function (): void {
    $result = Result::failure('error');

    expect($result->unwrapOr('default'))->toBe('default');
});

test('error returns the error on failure', function (): void {
    $result = Result::failure('my error');

    expect($result->error())->toBe('my error');
});

test('error throws on success', function (): void {
    $result = Result::success('value');

    $result->error();
})->throws(UnwrapFailure::class, 'Cannot access error on a success Result.');

test('map transforms the success value', function (): void {
    $result = Result::success(5);

    $mapped = $result->map(fn (int $value): int => $value * 2);

    expect($mapped->isSuccess())->toBeTrue()
        ->and($mapped->unwrap())->toBe(10);
});

test('map does not transform failure', function (): void {
    $result = Result::failure('error');

    $mapped = $result->map(fn (mixed $value): string => 'transformed ' . $value);

    expect($mapped->isFailure())->toBeTrue()
        ->and($mapped->error())->toBe('error');
});

test('flatMap chains successful results', function (): void {
    $result = Result::success(5);

    $chained = $result->flatMap(fn (int $value): Result => Result::success($value + 10));

    expect($chained->isSuccess())->toBeTrue()
        ->and($chained->unwrap())->toBe(15);
});

test('flatMap propagates failure from original result', function (): void {
    $result = Result::failure('original error');

    $chained = $result->flatMap(fn (mixed $value): Result => Result::success('should not reach ' . $value));

    expect($chained->isFailure())->toBeTrue()
        ->and($chained->error())->toBe('original error');
});

test('flatMap can return failure from callback', function (): void {
    $result = Result::success(5);

    $chained = $result->flatMap(fn (int $value): Result => Result::failure('chained error for ' . $value));

    expect($chained->isFailure())->toBeTrue()
        ->and($chained->error())->toBe('chained error for 5');
});

test('success works with null value', function (): void {
    $result = Result::success(null);

    expect($result->isSuccess())->toBeTrue()
        ->and($result->unwrap())->toBeNull();
});

test('failure works with enum error', function (): void {
    $result = Result::failure(TestResultError::NOT_FOUND);

    expect($result->isFailure())->toBeTrue()
        ->and($result->error())->toBe(TestResultError::NOT_FOUND);
});

enum TestResultError: string
{

    case VALIDATION = 'validation_failed';
    case NOT_FOUND = 'not_found';

}
