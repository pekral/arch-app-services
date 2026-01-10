<?php

declare(strict_types = 1);

use Pekral\Arch\Result\Result;

test('success creates result with value', function (): void {
    $value = 'test value';

    $result = Result::success($value);

    expect($result->isSuccess())->toBeTrue()
        ->and($result->isFailure())->toBeFalse()
        ->and($result->value())->toBe($value);
});

test('failure creates result with error', function (): void {
    $error = 'error message';

    $result = Result::failure($error);

    expect($result->isFailure())->toBeTrue()
        ->and($result->isSuccess())->toBeFalse()
        ->and($result->error())->toBe($error);
});

test('value throws exception on failure result', function (): void {
    $result = Result::failure('error');

    $result->value();
})->throws(RuntimeException::class, 'Cannot get value from failed result');

test('error throws exception on success result', function (): void {
    $result = Result::success('value');

    $result->error();
})->throws(RuntimeException::class, 'Cannot get error from successful result');

test('map transforms value on success', function (): void {
    $result = Result::success(5);

    $mapped = $result->map(fn (int $value): int => $value * 2);

    expect($mapped->isSuccess())->toBeTrue()
        ->and($mapped->value())->toBe(10);
});

test('map returns failure unchanged', function (): void {
    $error = 'error';
    $result = Result::failure($error);

    $mapped = $result->map(static fn (mixed $value): int => (int) $value);

    expect($mapped->isFailure())->toBeTrue()
        ->and($mapped->error())->toBe($error);
});

test('flatMap chains successful results', function (): void {
    $result = Result::success(5);

    $chained = $result->flatMap(fn (int $value): Result => Result::success($value * 2));

    expect($chained->isSuccess())->toBeTrue()
        ->and($chained->value())->toBe(10);
});

test('flatMap returns failure on failed result', function (): void {
    $error = 'error';
    $result = Result::failure($error);

    $chained = $result->flatMap(static fn (mixed $value): Result => Result::success((int) $value));

    expect($chained->isFailure())->toBeTrue()
        ->and($chained->error())->toBe($error);
});

test('onFailure executes callback on failure', function (): void {
    $error = 'error message';
    $result = Result::failure($error);
    $capturedError = new ArrayObject(['value' => null]);

    $returned = $result->onFailure(function (string $e) use ($capturedError): void {
        $capturedError['value'] = $e;
    });

    expect($capturedError['value'])->toBe($error)
        ->and($returned)->toBe($result);
});

test('onFailure does not execute callback on success', function (): void {
    $result = Result::success('value');
    $state = new ArrayObject(['executed' => false]);

    $returned = $result->onFailure(function (mixed $error) use ($state): void {
        $state['executed'] = (bool) $error;
    });

    expect($state['executed'])->toBeFalse()
        ->and($returned)->toBe($result);
});

test('onSuccess executes callback on success', function (): void {
    $value = 'test value';
    $result = Result::success($value);
    $capturedValue = new ArrayObject(['value' => null]);

    $returned = $result->onSuccess(function (string $v) use ($capturedValue): void {
        $capturedValue['value'] = $v;
    });

    expect($capturedValue['value'])->toBe($value)
        ->and($returned)->toBe($result);
});

test('onSuccess does not execute callback on failure', function (): void {
    $result = Result::failure('error');
    $state = new ArrayObject(['executed' => false]);

    $returned = $result->onSuccess(function (mixed $value) use ($state): void {
        $state['executed'] = (bool) $value;
    });

    expect($state['executed'])->toBeFalse()
        ->and($returned)->toBe($result);
});

test('valueOr returns value on success', function (): void {
    $result = Result::success('actual value');

    expect($result->valueOr('default'))->toBe('actual value');
});

test('valueOr returns default on failure', function (): void {
    $result = Result::failure('error');

    expect($result->valueOr('default'))->toBe('default');
});

test('mapError transforms error on failure', function (): void {
    $result = Result::failure('original error');

    $mapped = $result->mapError(fn (string $error): string => 'transformed: ' . $error);

    expect($mapped->isFailure())->toBeTrue()
        ->and($mapped->error())->toBe('transformed: original error');
});

test('mapError returns success unchanged', function (): void {
    $value = 'success value';
    $result = Result::success($value);

    $mapped = $result->mapError(static fn (mixed $error): string => (string) $error);

    expect($mapped->isSuccess())->toBeTrue()
        ->and($mapped->value())->toBe($value);
});

test('success works with null value', function (): void {
    $result = Result::success(null);

    expect($result->isSuccess())->toBeTrue()
        ->and($result->value())->toBeNull();
});

test('failure works with null error', function (): void {
    $result = Result::failure(null);

    expect($result->isFailure())->toBeTrue()
        ->and($result->error())->toBeNull();
});

test('success works with object value', function (): void {
    $object = new stdClass();
    $object->name = 'test';

    $result = Result::success($object);

    expect($result->isSuccess())->toBeTrue()
        ->and($result->value())->toBe($object);
});

test('failure works with object error', function (): void {
    $exception = new RuntimeException('test error');

    $result = Result::failure($exception);

    expect($result->isFailure())->toBeTrue()
        ->and($result->error())->toBe($exception);
});

test('flatMap can chain to failure result', function (): void {
    $result = Result::success(5);

    $chained = $result->flatMap(static fn (int $value): Result => Result::failure('validation failed for ' . $value));

    expect($chained->isFailure())->toBeTrue()
        ->and($chained->error())->toBe('validation failed for 5');
});

test('map and flatMap can be chained', function (): void {
    $result = Result::success(5);

    $chained = $result
        ->map(fn (int $value): int => $value * 2)
        ->flatMap(fn (int $value): Result => Result::success('Value: ' . $value))
        ->map(fn (string $value): string => strtoupper($value));

    expect($chained->isSuccess())->toBeTrue()
        ->and($chained->value())->toBe('VALUE: 10');
});

test('chain stops at first failure', function (): void {
    $result = Result::success(5);

    $chained = $result
        ->map(fn (int $value): int => $value * 2)
        ->flatMap(static fn (int $value): Result => Result::failure('error at step 2, value was ' . $value))
        ->map(fn (int $value): int => $value * 100);

    expect($chained->isFailure())->toBeTrue()
        ->and($chained->error())->toBe('error at step 2, value was 10');
});

test('success works with array value', function (): void {
    $array = ['key' => 'value', 'nested' => ['a', 'b']];

    $result = Result::success($array);

    expect($result->isSuccess())->toBeTrue()
        ->and($result->value())->toBe($array);
});

test('failure works with array error', function (): void {
    $errors = ['field1' => 'required', 'field2' => 'invalid'];

    $result = Result::failure($errors);

    expect($result->isFailure())->toBeTrue()
        ->and($result->error())->toBe($errors);
});
