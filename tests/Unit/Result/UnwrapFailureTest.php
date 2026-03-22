<?php

declare(strict_types = 1);

use Pekral\Arch\Result\UnwrapFailure;

test('fromResult creates exception with generic message for non-enum error', function (): void {
    $exception = UnwrapFailure::fromResult('some error');

    expect($exception)->toBeInstanceOf(UnwrapFailure::class)
        ->and($exception->getMessage())->toBe('Cannot unwrap a failure Result.');
});

test('fromResult creates exception with enum value in message for backed enum', function (): void {
    $exception = UnwrapFailure::fromResult(UnwrapTestError::FAILED);

    expect($exception)->toBeInstanceOf(UnwrapFailure::class)
        ->and($exception->getMessage())->toBe('Cannot unwrap a failure Result. Error: failed');
});

test('calledErrorOnSuccess creates exception with correct message', function (): void {
    $exception = UnwrapFailure::calledErrorOnSuccess();

    expect($exception)->toBeInstanceOf(UnwrapFailure::class)
        ->and($exception->getMessage())->toBe('Cannot access error on a success Result.');
});

enum UnwrapTestError: string
{

    case FAILED = 'failed';

}
