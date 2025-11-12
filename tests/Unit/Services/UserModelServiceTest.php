<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests\Unit\Services;

use Pekral\Arch\Examples\Services\User\UserModelManager;
use Pekral\Arch\Examples\Services\User\UserModelService;
use Pekral\Arch\Examples\Services\User\UserRepository;
use Pekral\Arch\Tests\Models\User;
use ReflectionClass;

beforeEach(function (): void {
    $this->userModelService = app(UserModelService::class);
});

test('get model manager returns correct instance', function (): void {
    $manager = $this->userModelService->getModelManager();

    expect($manager)->toBeInstanceOf(UserModelManager::class);
});

test('get repository returns correct instance', function (): void {
    $repository = $this->userModelService->getRepository();

    expect($repository)->toBeInstanceOf(UserRepository::class);
});

test('create creates new user', function (): void {
    $data = [
        'email' => 'test@example.com',
        'name' => 'Test User',
        'password' => 'password123',
    ];

    $user = $this->userModelService->create($data);

    expect($user)->toBeInstanceOf(User::class)
        ->and($user->name)->toBe('Test User')
        ->and($user->email)->toBe('test@example.com');
});

test('get model class returns correct class name', function (): void {
    $reflection = new ReflectionClass($this->userModelService);
    $method = $reflection->getMethod('getModelClass');
    $method->setAccessible(true);

    $modelClass = $method->invoke($this->userModelService);

    expect($modelClass)->toBe(User::class);
});
