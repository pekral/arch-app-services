<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests\DynamoDb\ModelManager;

use Pekral\Arch\Examples\Models\DynamoDb\User;
use Pekral\Arch\Examples\Services\DynamoDb\User\UserModelManager;
use Pekral\Arch\Exceptions\MassUpdateNotAvailable;

test('delete by params deletes matching record', function (): void {
    $manager = app(UserModelManager::class);
    $user = new User(['id' => '1', 'email' => 'delete@example.com', 'name' => 'Delete']);
    $user->save();

    $result = $manager->deleteByParams(['email' => 'delete@example.com']);

    expect($result)->toBeTrue();
    
    $found = User::where('email', 'delete@example.com')->first();
    expect($found)->toBeNull();
});

test('delete by params returns false when no records found', function (): void {
    $manager = app(UserModelManager::class);

    $result = $manager->deleteByParams(['name' => 'NonExistent']);

    expect($result)->toBeFalse();
});

test('bulk delete by params deletes matching records', function (): void {
    $manager = app(UserModelManager::class);
    $user1 = new User(['id' => '2', 'name' => 'John', 'email' => 'john1@example.com']);
    $user1->save();
    $user2 = new User(['id' => '3', 'name' => 'John', 'email' => 'john2@example.com']);
    $user2->save();

    $manager->bulkDeleteByParams(['name' => 'John']);

    $count = User::where('name', 'John')->count();
    expect($count)->toBe(0);
});

test('delete deletes model instance', function (): void {
    $manager = app(UserModelManager::class);
    $user = new User(['id' => '4', 'email' => 'delete2@example.com', 'name' => 'Delete2']);
    $user->save();

    $result = $manager->delete($user);

    expect($result)->toBeTrue();
    
    $found = User::where('email', 'delete2@example.com')->first();
    expect($found)->toBeNull();
});

test('delete returns false when model delete returns null', function (): void {
    $manager = app(UserModelManager::class);
    $user = new User(['id' => 'delete-null', 'email' => 'delete-null@example.com', 'name' => 'Delete Null']);
    $user->save();
    $user->delete();

    $result = $manager->delete($user);

    expect($result)->toBeFalse();
});

test('create creates new record', function (): void {
    $manager = app(UserModelManager::class);
    $user = $manager->create([
        'id' => '5',
        'email' => 'new@example.com',
        'name' => 'New User',
        'password' => 'password123',
    ]);

    expect($user)->toBeInstanceOf(User::class)
        ->and($user->name)->toBe('New User');
    
    $found = User::where('email', 'new@example.com')->first();
    expect($found)->not->toBeNull();
});

test('update updates existing record', function (): void {
    $manager = app(UserModelManager::class);
    $user = new User(['id' => '6', 'name' => 'Old Name', 'email' => 'old@example.com']);
    $user->save();

    $result = $manager->update($user, ['name' => 'New Name']);

    expect($result)->toBeTrue();
    
    $user->refresh();
    expect($user->name)->toBe('New Name');
});

test('bulk create creates multiple records', function (): void {
    $manager = app(UserModelManager::class);
    $result = $manager->bulkCreate([
        ['id' => '7', 'name' => 'User 1', 'email' => 'user1@example.com', 'password' => 'pass1'],
        ['id' => '8', 'name' => 'User 2', 'email' => 'user2@example.com', 'password' => 'pass2'],
        ['id' => '9', 'name' => 'User 3', 'email' => 'user3@example.com', 'password' => 'pass3'],
    ]);

    expect($result)->toBe(3);
    
    $found1 = User::where('email', 'user1@example.com')->first();
    $found2 = User::where('email', 'user2@example.com')->first();
    $found3 = User::where('email', 'user3@example.com')->first();
    
    expect($found1)->not->toBeNull()
        ->and($found2)->not->toBeNull()
        ->and($found3)->not->toBeNull();
});

test('bulk create with empty data returns zero', function (): void {
    $manager = app(UserModelManager::class);
    $result = $manager->bulkCreate([]);

    expect($result)->toBe(0);
});

test('insert or ignore with empty data', function (): void {
    $manager = app(UserModelManager::class);
    $manager->insertOrIgnore([]);

    expect(true)->toBeTrue();
});

test('insert or ignore with valid data', function (): void {
    $manager = app(UserModelManager::class);
    $manager->insertOrIgnore([
        ['id' => '10', 'name' => 'John Doe', 'email' => 'john@example.com', 'password' => 'password123'],
        ['id' => '11', 'name' => 'Jane Smith', 'email' => 'jane@example.com', 'password' => 'password456'],
    ]);

    $found1 = User::where('email', 'john@example.com')->first();
    $found2 = User::where('email', 'jane@example.com')->first();
    
    expect($found1)->not->toBeNull()
        ->and($found2)->not->toBeNull();
});

test('bulk update updates multiple records', function (): void {
    $manager = app(UserModelManager::class);
    $user1 = new User(['id' => '12', 'name' => 'User 1', 'email' => 'user5@example.com']);
    $user1->save();
    $user2 = new User(['id' => '13', 'name' => 'User 2', 'email' => 'user6@example.com']);
    $user2->save();

    $result = $manager->bulkUpdate([
        ['id' => '12', 'name' => 'Updated User 1'],
        ['id' => '13', 'name' => 'Updated User 2'],
    ]);

    expect($result)->toBe(2);
    
    $user1->refresh();
    $user2->refresh();
    expect($user1->name)->toBe('Updated User 1')
        ->and($user2->name)->toBe('Updated User 2');
});

test('bulk update with empty array returns zero', function (): void {
    $manager = app(UserModelManager::class);
    $result = $manager->bulkUpdate([]);

    expect($result)->toBe(0);
});

test('bulk update with missing key column returns zero', function (): void {
    $manager = app(UserModelManager::class);
    $result = $manager->bulkUpdate([
        ['name' => 'Updated User 1'],
        ['name' => 'Updated User 2'],
    ]);

    expect($result)->toBe(0);
});

test('bulk update with non-existent key returns zero', function (): void {
    $manager = app(UserModelManager::class);
    $result = $manager->bulkUpdate([
        ['id' => 'non-existent-1', 'name' => 'Updated User 1'],
        ['id' => 'non-existent-2', 'name' => 'Updated User 2'],
    ]);

    expect($result)->toBe(0);
});

test('update or create creates new record', function (): void {
    $manager = app(UserModelManager::class);
    $uniqueEmail = 'newuser-' . uniqid() . '@example.com';
    $result = $manager->updateOrCreate(
        ['email' => $uniqueEmail],
        ['id' => '14', 'name' => 'New User', 'password' => 'password123'],
    );

    expect($result)->toBeInstanceOf(User::class);
    
    $found = User::where('email', $uniqueEmail)->first();
    expect($found)->not->toBeNull()
        ->and($found->name)->toBe('New User');
});

test('update or create updates existing record', function (): void {
    $manager = app(UserModelManager::class);
    $existingUser = new User(['id' => '15', 'email' => 'existing@example.com', 'name' => 'Original Name']);
    $existingUser->save();

    $result = $manager->updateOrCreate(
        ['email' => 'existing@example.com'],
        ['name' => 'Updated Name'],
    );

    expect($result->id)->toBe($existingUser->id);
    
    $existingUser->refresh();
    expect($existingUser->name)->toBe('Updated Name');
});

test('get or create creates new record', function (): void {
    $manager = app(UserModelManager::class);
    $uniqueEmail = 'newuser2-' . uniqid() . '@example.com';
    $result = $manager->getOrCreate(
        ['email' => $uniqueEmail],
        ['id' => '16', 'name' => 'New User', 'password' => 'password123'],
    );

    expect($result)->toBeInstanceOf(User::class);
    
    $found = User::where('email', $uniqueEmail)->first();
    expect($found)->not->toBeNull()
        ->and($found->name)->toBe('New User');
});

test('get or create returns existing record', function (): void {
    $manager = app(UserModelManager::class);
    $existingUser = new User(['id' => '17', 'email' => 'existing2@example.com', 'name' => 'Original Name']);
    $existingUser->save();

    $result = $manager->getOrCreate(
        ['email' => 'existing2@example.com'],
        ['name' => 'Updated Name'],
    );

    expect($result->id)->toBe($existingUser->id);
    
    $existingUser->refresh();
    expect($existingUser->name)->toBe('Original Name')
        ->and($result->name)->toBe('Original Name');
});

test('raw mass update throws exception', function (): void {
    $manager = app(UserModelManager::class);

    $manager->rawMassUpdate([
        ['id' => '1', 'name' => 'John Updated'],
    ]);
})->throws(MassUpdateNotAvailable::class, 'not supported for DynamoDB');

test('create new model instance', function (): void {
    $manager = app(UserModelManager::class);

    $result = $manager->createNewModelInstance();

    expect($result)->toBeInstanceOf(User::class);
});
