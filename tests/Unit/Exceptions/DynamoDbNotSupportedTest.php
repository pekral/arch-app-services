<?php

declare(strict_types = 1);

use Pekral\Arch\Exceptions\DynamoDbNotSupported;

test('feature not supported creates exception with correct message', function (): void {
    $feature = 'GROUP BY';

    $exception = DynamoDbNotSupported::featureNotSupported($feature);

    expect($exception)->toBeInstanceOf(DynamoDbNotSupported::class)
        ->and($exception->getMessage())->toContain('DynamoDB does not support')
        ->and($exception->getMessage())->toContain($feature)
        ->and($exception->getMessage())->toContain('DynamoDb repositories');
});

test('group by not supported creates exception with correct message', function (): void {
    $exception = DynamoDbNotSupported::groupByNotSupported();

    expect($exception)->toBeInstanceOf(DynamoDbNotSupported::class)
        ->and($exception->getMessage())->toContain('DynamoDB does not support')
        ->and($exception->getMessage())->toContain('GROUP BY')
        ->and($exception->getMessage())->toContain('DynamoDb repositories');
});

test('eager loading not supported creates exception with correct message', function (): void {
    $exception = DynamoDbNotSupported::eagerLoadingNotSupported();

    expect($exception)->toBeInstanceOf(DynamoDbNotSupported::class)
        ->and($exception->getMessage())->toContain('DynamoDB does not support')
        ->and($exception->getMessage())->toContain('eager loading')
        ->and($exception->getMessage())->toContain('DynamoDb repositories');
});

test('order by not supported creates exception with correct message', function (): void {
    $exception = DynamoDbNotSupported::orderByNotSupported();

    expect($exception)->toBeInstanceOf(DynamoDbNotSupported::class)
        ->and($exception->getMessage())->toContain('DynamoDB does not support')
        ->and($exception->getMessage())->toContain('ORDER BY')
        ->and($exception->getMessage())->toContain('DynamoDb repositories');
});

test('raw mass update not supported creates exception with correct message', function (): void {
    $exception = DynamoDbNotSupported::rawMassUpdateNotSupported();

    expect($exception)->toBeInstanceOf(DynamoDbNotSupported::class)
        ->and($exception->getMessage())->toContain('DynamoDB does not support')
        ->and($exception->getMessage())->toContain('raw mass update')
        ->and($exception->getMessage())->toContain('DynamoDb repositories');
});
