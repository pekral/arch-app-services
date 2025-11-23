<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests\Unit\Services\User\DynamoDb;

use Pekral\Arch\Examples\Models\DynamoDb\UserDynamoDb;
use Pekral\Arch\Examples\Services\User\DynamoDb\UserDynamoDbModelManager;
use ReflectionClass;

test('get model class name returns correct class', function (): void {
    $manager = app(UserDynamoDbModelManager::class);
    $reflection = new ReflectionClass($manager);
    $method = $reflection->getMethod('getModelClassName');
    $method->setAccessible(true);

    $modelClassName = $method->invoke($manager);

    expect($modelClassName)->toBe(UserDynamoDb::class);
});
