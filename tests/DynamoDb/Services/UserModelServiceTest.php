<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests\DynamoDb\Services;

use Pekral\Arch\Examples\Models\DynamoDb\User;
use Pekral\Arch\Examples\Services\DynamoDb\User\UserModelManager;
use Pekral\Arch\Examples\Services\DynamoDb\User\UserModelService;
use Pekral\Arch\Examples\Services\DynamoDb\User\UserRepository;
use ReflectionClass;

test('get model manager returns correct instance', function (): void {
    $userModelManager = app(UserModelManager::class);
    $userRepository = app(UserRepository::class);
    $service = new UserModelService($userModelManager, $userRepository);

    $result = $service->getModelManager();

    expect($result)->toBeInstanceOf(UserModelManager::class);
});

test('get repository returns correct instance', function (): void {
    $userModelManager = app(UserModelManager::class);
    $userRepository = app(UserRepository::class);
    $service = new UserModelService($userModelManager, $userRepository);

    $result = $service->getRepository();

    expect($result)->toBeInstanceOf(UserRepository::class);
});

test('get model class returns correct class name', function (): void {
    $userModelManager = app(UserModelManager::class);
    $userRepository = app(UserRepository::class);
    $service = new UserModelService($userModelManager, $userRepository);

    $reflection = new ReflectionClass($service);
    $method = $reflection->getMethod('getModelClass');
    $method->setAccessible(true);

    $result = $method->invoke($service);

    expect($result)->toBe(User::class);
});

test('create creates new user', function (): void {
    $userModelManager = app(UserModelManager::class);
    $userRepository = app(UserRepository::class);
    $service = new UserModelService($userModelManager, $userRepository);

    $user = $service->create([
        'id' => '1',
        'email' => 'test@example.com',
        'name' => 'Test User',
        'password' => 'password123',
    ]);

    expect($user)->toBeInstanceOf(User::class)
        ->and($user->email)->toBe('test@example.com');
});

test('user repository get model class name', function (): void {
    $repository = app(UserRepository::class);
    $reflection = new ReflectionClass($repository);
    $method = $reflection->getMethod('getModelClassName');
    $method->setAccessible(true);

    $result = $method->invoke($repository);

    expect($result)->toBe(User::class);
});

test('user model manager get model class name', function (): void {
    $manager = app(UserModelManager::class);
    $reflection = new ReflectionClass($manager);
    $method = $reflection->getMethod('getModelClassName');
    $method->setAccessible(true);

    $result = $method->invoke($manager);

    expect($result)->toBe(User::class);
});
