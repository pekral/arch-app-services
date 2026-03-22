<?php

declare(strict_types = 1);

use Pekral\Arch\Result\Result;
use Pekral\Arch\Result\Success;
use Pekral\Arch\Result\UnwrapFailure;

test('success is instance of Result', function (): void {
    $success = new Success('value');

    expect($success)->toBeInstanceOf(Result::class);
});

test('success isSuccess returns true', function (): void {
    $success = new Success('value');

    expect($success->isSuccess())->toBeTrue();
});

test('success isFailure returns false', function (): void {
    $success = new Success('value');

    expect($success->isFailure())->toBeFalse();
});

test('success unwrap returns the value', function (): void {
    $success = new Success('hello');

    expect($success->unwrap())->toBe('hello');
});

test('success unwrapOr returns the value ignoring default', function (): void {
    $success = new Success('hello');

    expect($success->unwrapOr('default'))->toBe('hello');
});

test('success error throws', function (): void {
    $success = new Success('value');

    $success->error();
})->throws(UnwrapFailure::class, 'Cannot access error on a success Result.');

test('success map transforms the value', function (): void {
    $success = new Success(10);

    $mapped = $success->map(fn (int $value): string => 'number: ' . $value);

    expect($mapped->isSuccess())->toBeTrue()
        ->and($mapped->unwrap())->toBe('number: 10');
});

test('success flatMap chains to new result', function (): void {
    $success = new Success(5);

    $chained = $success->flatMap(fn (int $value): Result => Result::success($value * 3));

    expect($chained->isSuccess())->toBeTrue()
        ->and($chained->unwrap())->toBe(15);
});
