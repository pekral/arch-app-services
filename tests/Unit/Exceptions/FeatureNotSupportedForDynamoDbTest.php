<?php

declare(strict_types = 1);

use Pekral\Arch\Exceptions\FeatureNotSupportedForDynamoDb;

test('eager loading creates exception with correct message', function (): void {
    $exception = FeatureNotSupportedForDynamoDb::eagerLoading();

    expect($exception)->toBeInstanceOf(FeatureNotSupportedForDynamoDb::class)
        ->and($exception->getMessage())->toContain('Eager loading')
        ->toContain('not supported')
        ->toContain('DynamoDB');
});

test('order by creates exception with correct message', function (): void {
    $exception = FeatureNotSupportedForDynamoDb::orderBy();

    expect($exception)->toBeInstanceOf(FeatureNotSupportedForDynamoDb::class)
        ->and($exception->getMessage())->toContain('orderBy')
        ->toContain('not supported')
        ->toContain('DynamoDB');
});

test('group by creates exception with correct message', function (): void {
    $exception = FeatureNotSupportedForDynamoDb::groupBy();

    expect($exception)->toBeInstanceOf(FeatureNotSupportedForDynamoDb::class)
        ->and($exception->getMessage())->toContain('GroupBy')
        ->toContain('not supported')
        ->toContain('DynamoDB');
});
