<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests\Unit\Services;

use Pekral\Arch\Examples\Services\User\UserModelManager;
use Pekral\Arch\Examples\Services\User\UserModelService;
use Pekral\Arch\Examples\Services\User\UserRepository;
use Pekral\Arch\Tests\Models\User;
use ReflectionClass;

test('get model manager returns correct instance', function (): void {
    $userModelService = app(UserModelService::class);
    $manager = $userModelService->getModelManager();

    expect($manager)->toBeInstanceOf(UserModelManager::class);
});

test('get repository returns correct instance', function (): void {
    $userModelService = app(UserModelService::class);
    $repository = $userModelService->getRepository();

    expect($repository)->toBeInstanceOf(UserRepository::class);
});

test('create creates new user', function (): void {
    $userModelService = app(UserModelService::class);
    $data = [
        'email' => 'test@example.com',
        'name' => 'Test User',
        'password' => 'password123',
    ];

    $user = $userModelService->create($data);

    expect($user)->toBeInstanceOf(User::class)
        ->and($user->name)->toBe('Test User')
        ->and($user->email)->toBe('test@example.com');
});

test('get model class returns correct class name', function (): void {
    $userModelService = app(UserModelService::class);
    $reflection = new ReflectionClass($userModelService);
    $method = $reflection->getMethod('getModelClass');
    $method->setAccessible(true);

    $modelClass = $method->invoke($userModelService);

    expect($modelClass)->toBe(User::class);
});
