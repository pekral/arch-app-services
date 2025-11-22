<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests\DynamoDb\Actions\User;

use Illuminate\Validation\ValidationException;
use Pekral\Arch\Examples\Actions\DynamoDb\User\CreateUser;
use Pekral\Arch\Examples\Models\DynamoDb\User;
use Pekral\Arch\Examples\Services\DynamoDb\User\UserModelService;

test('execute creates user with valid data', function (): void {
    $userModelService = app(UserModelService::class);
    $action = new CreateUser($userModelService);

    $result = $action->execute([
        'id' => '1',
        'email' => 'test@example.com',
        'name' => 'Test User',
    ]);

    expect($result)->toBeInstanceOf(User::class)
        ->and($result->email)->toBe('test@example.com')
        ->and($result->name)->toBe('Test User');
    
    $found = User::where('email', 'test@example.com')->first();
    expect($found)->not->toBeNull();
});

test('execute throws validation exception with invalid data', function (): void {
    $userModelService = app(UserModelService::class);
    $action = new CreateUser($userModelService);

    $action->execute([
        'email' => 'invalid-email',
    ]);
})->throws(ValidationException::class);

test('execute throws validation exception with missing required fields', function (): void {
    $userModelService = app(UserModelService::class);
    $action = new CreateUser($userModelService);

    $action->execute([]);
})->throws(ValidationException::class);
