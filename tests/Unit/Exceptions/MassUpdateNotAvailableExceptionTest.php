<?php

declare(strict_types = 1);

use Pekral\Arch\Exceptions\MassUpdateNotAvailable;

test('missing package creates exception with correct message', function (): void {
    $exception = MassUpdateNotAvailable::missingPackage();

    expect($exception)->toBeInstanceOf(MassUpdateNotAvailable::class)
        ->and($exception->getMessage())->toContain('iksaku/laravel-mass-update')
        ->toContain('composer require');
});

test('trait not used creates exception with correct message', function (): void {
    $modelClass = 'App\Models\User';

    $exception = MassUpdateNotAvailable::traitNotUsed($modelClass);

    expect($exception)->toBeInstanceOf(MassUpdateNotAvailable::class)
        ->and($exception->getMessage())->toContain($modelClass)
        ->and($exception->getMessage())->toContain('MassUpdatable')
        ->and($exception->getMessage())->toContain('use Iksaku\Laravel\MassUpdate\MassUpdatable');
});

test('not supported for dynamo db creates exception with correct message', function (): void {
    $exception = MassUpdateNotAvailable::notSupportedForDynamoDb();

    expect($exception)->toBeInstanceOf(MassUpdateNotAvailable::class)
        ->and($exception->getMessage())->toContain('Mass update')
        ->and($exception->getMessage())->toContain('not supported')
        ->and($exception->getMessage())->toContain('DynamoDB');
});
