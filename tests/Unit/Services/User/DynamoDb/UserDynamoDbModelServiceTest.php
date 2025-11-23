<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests\Unit\Services\User\DynamoDb;

use Pekral\Arch\Examples\Models\DynamoDb\UserDynamoDb;
use Pekral\Arch\Examples\Services\User\DynamoDb\UserDynamoDbModelManager;
use Pekral\Arch\Examples\Services\User\DynamoDb\UserDynamoDbModelService;
use Pekral\Arch\Examples\Services\User\DynamoDb\UserDynamoDbRepository;
use ReflectionClass;

test('get model manager returns correct instance', function (): void {
    $userDynamoDbModelService = app(UserDynamoDbModelService::class);
    $manager = $userDynamoDbModelService->getModelManager();

    expect($manager)->toBeInstanceOf(UserDynamoDbModelManager::class);
});

test('get repository returns correct instance', function (): void {
    $userDynamoDbModelService = app(UserDynamoDbModelService::class);
    $repository = $userDynamoDbModelService->getRepository();

    expect($repository)->toBeInstanceOf(UserDynamoDbRepository::class);
});

test('get model class returns correct class name', function (): void {
    $userDynamoDbModelService = app(UserDynamoDbModelService::class);
    $reflection = new ReflectionClass($userDynamoDbModelService);
    $method = $reflection->getMethod('getModelClass');
    $method->setAccessible(true);

    $modelClass = $method->invoke($userDynamoDbModelService);

    expect($modelClass)->toBe(UserDynamoDb::class);
});
