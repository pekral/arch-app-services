<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests\Unit\Result;

use Pekral\Arch\Result\Result;
use RuntimeException;
use stdClass;

test('success creates successful result', function (): void {
    $value = 'test value';

    $result = Result::success($value);

    expect($result)->toBeInstanceOf(Result::class)
        ->and($result->isSuccess())->toBeTrue()
        ->and($result->isFailure())->toBeFalse()
        ->and($result->value())->toBe($value);
});

test('failure creates failed result', function (): void {
    $error = 'test error';

    $result = Result::failure($error);

    expect($result)->toBeInstanceOf(Result::class)
        ->and($result->isSuccess())->toBeFalse()
        ->and($result->isFailure())->toBeTrue()
        ->and($result->error())->toBe($error);
});

test('value throws exception when result is failure', function (): void {
    $result = Result::failure('error');

    $result->value();
})->throws(RuntimeException::class, 'Cannot get value from failed result');

test('error throws exception when result is success', function (): void {
    $result = Result::success('value');

    $result->error();
})->throws(RuntimeException::class, 'Cannot get error from successful result');

test('map transforms value on success', function (): void {
    $result = Result::success(5);
    $callback = fn (int $value): int => $value * 2;

    $mapped = $result->map($callback);

    expect($mapped)->toBeInstanceOf(Result::class)
        ->and($mapped->isSuccess())->toBeTrue()
        ->and($mapped->value())->toBe(10);
});

test('map preserves error on failure', function (): void {
    $error = 'original error';
    $result = Result::failure($error);
    $callback = fn (int $value): int => $value * 2;

    $mapped = $result->map($callback);

    expect($mapped)->toBeInstanceOf(Result::class)
        ->and($mapped->isFailure())->toBeTrue()
        ->and($mapped->error())->toBe($error);
});

test('map transforms value type on success', function (): void {
    $result = Result::success(5);
    $callback = fn (int $value): string => (string) $value;

    $mapped = $result->map($callback);

    expect($mapped)->toBeInstanceOf(Result::class)
        ->and($mapped->isSuccess())->toBeTrue()
        ->and($mapped->value())->toBe('5');
});

test('flatMap chains results on success', function (): void {
    $result = Result::success(5);
    $callback = fn (int $value): Result => Result::success($value * 2);

    $chained = $result->flatMap($callback);

    expect($chained)->toBeInstanceOf(Result::class)
        ->and($chained->isSuccess())->toBeTrue()
        ->and($chained->value())->toBe(10);
});

test('flatMap preserves error on failure', function (): void {
    $error = 'original error';
    $result = Result::failure($error);
    $callback = fn (int $value): Result => Result::success($value * 2);

    $chained = $result->flatMap($callback);

    expect($chained)->toBeInstanceOf(Result::class)
        ->and($chained->isFailure())->toBeTrue()
        ->and($chained->error())->toBe($error);
});

test('flatMap can return failure from callback', function (): void {
    $result = Result::success(5);
    $newError = 'new error';
    $callback = (fn (): Result => Result::failure($newError));

    /** @phpstan-ignore-next-line */
    $chained = $result->flatMap($callback);

    expect($chained)->toBeInstanceOf(Result::class)
        ->and($chained->isFailure())->toBeTrue()
        ->and($chained->error())->toBe($newError);
});

test('onFailure calls callback on failure', function (): void {
    $error = 'test error';
    $result = Result::failure($error);
    $state = new stdClass();
    $state->called = false;
    $state->receivedError = null;

    $result->onFailure(function (mixed $err) use ($state): void {
        $state->called = true;
        $state->receivedError = $err;
    });

    expect($state->called)->toBeTrue()
        ->and($state->receivedError)->toBe($error);
});

test('onFailure does not call callback on success', function (): void {
    $result = Result::success('value');
    $state = new stdClass();
    $state->called = false;

    $result->onFailure(function () use ($state): void {
        $state->called = true;
    });

    expect($state->called)->toBeFalse();
});

test('onFailure returns same instance', function (): void {
    $result = Result::failure('error');

    $returned = $result->onFailure(function (): void {
    });

    expect($returned)->toBe($result);
});

test('onSuccess calls callback on success', function (): void {
    $value = 'test value';
    $result = Result::success($value);
    $state = new stdClass();
    $state->called = false;
    $state->receivedValue = null;

    $result->onSuccess(function (mixed $val) use ($state): void {
        $state->called = true;
        $state->receivedValue = $val;
    });

    expect($state->called)->toBeTrue()
        ->and($state->receivedValue)->toBe($value);
});

test('onSuccess does not call callback on failure', function (): void {
    $result = Result::failure('error');
    $state = new stdClass();
    $state->called = false;

    $result->onSuccess(function () use ($state): void {
        $state->called = true;
    });

    expect($state->called)->toBeFalse();
});

test('onSuccess returns same instance', function (): void {
    $result = Result::success('value');

    $returned = $result->onSuccess(function (): void {
    });

    expect($returned)->toBe($result);
});

test('success with null value', function (): void {
    $result = Result::success(null);

    expect($result)->toBeInstanceOf(Result::class)
        ->and($result->isSuccess())->toBeTrue()
        ->and($result->value())->toBeNull();
});

test('failure with null error', function (): void {
    $result = Result::failure(null);

    expect($result)->toBeInstanceOf(Result::class)
        ->and($result->isFailure())->toBeTrue()
        ->and($result->error())->toBeNull();
});

test('success with object value', function (): void {
    $object = new stdClass();
    $object->property = 'test';
    $result = Result::success($object);

    expect($result)->toBeInstanceOf(Result::class)
        ->and($result->isSuccess())->toBeTrue()
        ->and($result->value())->toBe($object)
        ->and($result->value()->property)->toBe('test');
});

test('failure with exception', function (): void {
    $exception = new RuntimeException('test exception');
    $result = Result::failure($exception);

    expect($result)->toBeInstanceOf(Result::class)
        ->and($result->isFailure())->toBeTrue()
        ->and($result->error())->toBe($exception)
        ->and($result->error()->getMessage())->toBe('test exception');
});

test('map with different return type', function (): void {
    $result = Result::success('hello');
    $callback = fn (string $value): array => str_split($value);

    $mapped = $result->map($callback);

    expect($mapped)->toBeInstanceOf(Result::class)
        ->and($mapped->isSuccess())->toBeTrue()
        ->and($mapped->value())->toBe(['h', 'e', 'l', 'l', 'o']);
});

test('flatMap with nested success chain', function (): void {
    $result = Result::success(2);
    $callback1 = fn (int $value): Result => Result::success($value * 3);
    $callback2 = fn (int $value): Result => Result::success($value + 1);

    $chained1 = $result->flatMap($callback1);
    $chained2 = $chained1->flatMap($callback2);

    expect($chained2)->toBeInstanceOf(Result::class)
        ->and($chained2->isSuccess())->toBeTrue()
        ->and($chained2->value())->toBe(7);
});
