<?php

declare(strict_types = 1);

use Pekral\Arch\Tests\Models\UserDynamoModel;
use Ramsey\Uuid\Uuid;

test('creates new user in dynamodb', function (): void {
    $userId = Uuid::uuid4()->toString();

    $user = new UserDynamoModel();
    $user->id = $userId;
    $user->name = 'John Doe';
    $user->email = 'john.doe@example.com';
    $user->password = 'hashed_password';

    $result = $user->save();

    expect($result)->toBeTrue()
        ->and($user->exists)->toBeTrue()
        ->and($user->id)->toBe($userId)
        ->and($user->name)->toBe('John Doe')
        ->and($user->email)->toBe('john.doe@example.com');
});

test('finds user by id in dynamodb', function (): void {
    $userId = Uuid::uuid4()->toString();

    $user = new UserDynamoModel();
    $user->id = $userId;
    $user->name = 'Jane Smith';
    $user->email = 'jane.smith@example.com';
    $user->password = 'hashed_password';
    $user->save();

    /** @var \Pekral\Arch\Tests\Models\UserDynamoModel $foundUser */
    $foundUser = UserDynamoModel::query()->find($userId);

    expect($foundUser)->not->toBeNull()
        ->and($foundUser->id)->toBe($userId)
        ->and($foundUser->name)->toBe('Jane Smith')
        ->and($foundUser->email)->toBe('jane.smith@example.com');
});

test('finds user by email using index', function (): void {
    $userId = Uuid::uuid4()->toString();
    $email = 'test.user@example.com';

    $user = new UserDynamoModel();
    $user->id = $userId;
    $user->name = 'Test User';
    $user->email = $email;
    $user->password = 'hashed_password';
    $user->save();

    /** @var \Pekral\Arch\Tests\Models\UserDynamoModel $foundUser */
    $foundUser = UserDynamoModel::where('email', $email)->first();

    expect($foundUser)->not->toBeNull()
        ->and($foundUser->email)->toBe($email)
        ->and($foundUser->name)->toBe('Test User');
});

test('updates user in dynamodb', function (): void {
    $userId = Uuid::uuid4()->toString();

    $user = new UserDynamoModel();
    $user->id = $userId;
    $user->name = 'Original Name';
    $user->email = 'original@example.com';
    $user->password = 'hashed_password';
    $user->save();

    $user->name = 'Updated Name';
    $result = $user->save();

    expect($result)->toBeTrue();

    /** @var \Pekral\Arch\Tests\Models\UserDynamoModel $updatedUser */
    $updatedUser = UserDynamoModel::find($userId);
    expect($updatedUser->name)->toBe('Updated Name');
});

test('deletes user from dynamodb', function (): void {
    $userId = Uuid::uuid4()->toString();

    $user = new UserDynamoModel();
    $user->id = $userId;
    $user->name = 'To Be Deleted';
    $user->email = 'delete@example.com';
    $user->password = 'hashed_password';
    $user->save();

    $result = $user->delete();

    expect($result)->toBeTrue();

    $deletedUser = UserDynamoModel::find($userId);
    expect($deletedUser)->toBeNull();
});
