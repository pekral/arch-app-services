<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests\Unit\Services\User\DynamoDb;

use Pekral\Arch\Examples\Models\DynamoDb\UserDynamoDb;
use Pekral\Arch\Examples\Services\User\DynamoDb\UserDynamoDbRepository;
use ReflectionClass;

test('get model class name returns correct class', function (): void {
    $repository = app(UserDynamoDbRepository::class);
    $reflection = new ReflectionClass($repository);
    $method = $reflection->getMethod('getModelClassName');
    $method->setAccessible(true);

    $modelClassName = $method->invoke($repository);

    expect($modelClassName)->toBe(UserDynamoDb::class);
});
