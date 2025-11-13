<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests\Unit\ModelManager\Mysql;

use Illuminate\Database\Eloquent\Model;
use Mockery;
use Pekral\Arch\Examples\Services\User\UserModelManager;
use Pekral\Arch\Exceptions\MassUpdateNotAvailable;
use Pekral\Arch\Tests\Models\User;
use Pekral\Arch\Tests\Unit\ModelManager\UserWithoutMassUpdateModelManager;

test('delete by params deletes matching record', function (): void {
    $manager = app(UserModelManager::class);
    User::factory()->create(['email' => 'delete@example.com']);

    $result = $manager->deleteByParams(['email' => 'delete@example.com']);

    expect($result)->toBeTrue()
        ->and(User::query()->where('email', 'delete@example.com')->first())->toBeNull();
});

test('bulk delete by params deletes matching records', function (): void {
    $manager = app(UserModelManager::class);
    User::factory()->count(5)->create(['name' => 'John']);
    User::factory()->count(3)->create(['name' => 'Jane']);

    $manager->bulkDeleteByParams(['name' => 'John']);

    expect(User::query()->where('name', 'John')->count())->toBe(0)
        ->and(User::query()->where('name', 'Jane')->count())->toBe(3);
});

test('bulk delete by params with multiple conditions', function (): void {
    $manager = app(UserModelManager::class);
    User::factory()->create(['name' => 'John', 'email' => 'john1@example.com']);
    User::factory()->create(['name' => 'John', 'email' => 'john2@example.com']);
    User::factory()->create(['name' => 'Jane', 'email' => 'jane@example.com']);

    $manager->bulkDeleteByParams(['name' => 'John', 'email' => 'john1@example.com']);

    expect(User::query()->where('name', 'John')->where('email', 'john1@example.com')->count())->toBe(0)
        ->and(User::query()->where('name', 'John')->count())->toBe(1);
});

test('bulk delete by params with no matching records does nothing', function (): void {
    $manager = app(UserModelManager::class);
    User::factory()->count(3)->create(['name' => 'John']);

    $manager->bulkDeleteByParams(['name' => 'NonExistent']);

    expect(User::query()->where('name', 'John')->count())->toBe(3);
});

test('bulk delete by params deletes all records when no conditions', function (): void {
    $manager = app(UserModelManager::class);
    User::factory()->count(5)->create();

    $manager->bulkDeleteByParams([]);

    expect(User::count())->toBe(0);
});

test('bulk delete by params with empty table does nothing', function (): void {
    $manager = app(UserModelManager::class);

    $manager->bulkDeleteByParams(['name' => 'John']);

    expect(User::count())->toBe(0);
});

test('delete deletes model instance', function (): void {
    $manager = app(UserModelManager::class);
    $user = User::factory()->create(['email' => 'delete@example.com']);

    $result = $manager->delete($user);

    expect($result)->toBeTrue()
        ->and(User::query()->where('email', 'delete@example.com')->first())->toBeNull();
});

test('delete returns false when model delete returns null', function (): void {
    $manager = app(UserModelManager::class);

    $mockUser = Mockery::mock(Model::class);
    $mockUser->shouldReceive('delete')->once()->andReturn(null);

    $result = $manager->delete($mockUser);

    expect($result)->toBeFalse();
});

test('create creates new record', function (): void {
    $manager = app(UserModelManager::class);
    $user = $manager->create([
        'email' => 'new@example.com',
        'name' => 'New User',
        'password' => 'password123',
    ]);

    expect($user)->toBeInstanceOf(User::class)
        ->and($user->name)->toBe('New User');
    
    expect(User::query()->where('email', 'new@example.com')->exists())->toBeTrue();
});

test('update updates existing record', function (): void {
    $manager = app(UserModelManager::class);
    $user = User::factory()->create(['name' => 'Old Name']);

    $result = $manager->update($user, ['name' => 'New Name']);

    expect($result)->toBeTrue();
    
    $user->refresh();
    expect($user->name)->toBe('New Name');
});

test('bulk create creates multiple records', function (): void {
    $manager = app(UserModelManager::class);
    $result = $manager->bulkCreate([
        ['name' => 'User 1', 'email' => 'user1@example.com', 'password' => 'pass1'],
        ['name' => 'User 2', 'email' => 'user2@example.com', 'password' => 'pass2'],
        ['name' => 'User 3', 'email' => 'user3@example.com', 'password' => 'pass3'],
    ]);

    expect($result)->toBe(3)
        ->and(User::query()->where('email', 'user1@example.com')->exists())->toBeTrue()
        ->and(User::query()->where('email', 'user2@example.com')->exists())->toBeTrue()
        ->and(User::query()->where('email', 'user3@example.com')->exists())->toBeTrue();
});

test('bulk create with empty data returns zero', function (): void {
    $manager = app(UserModelManager::class);
    $result = $manager->bulkCreate([]);

    expect($result)->toBe(0);
});

test('bulk update updates multiple records', function (): void {
    $manager = app(UserModelManager::class);
    $user1 = User::factory()->create(['name' => 'User 1']);
    $user2 = User::factory()->create(['name' => 'User 2']);

    $result = $manager->bulkUpdate([
        ['id' => $user1->id, 'name' => 'Updated User 1'],
        ['id' => $user2->id, 'name' => 'Updated User 2'],
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

test('bulk update with empty data after removing key returns zero', function (): void {
    $manager = app(UserModelManager::class);
    $user = User::factory()->create();

    $result = $manager->bulkUpdate([
        ['id' => $user->id],
    ]);

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
        ['name' => 'John Doe', 'email' => 'john@example.com', 'password' => 'password123'],
        ['name' => 'Jane Smith', 'email' => 'jane@example.com', 'password' => 'password456'],
    ]);

    expect(User::query()->where('email', 'john@example.com')->exists())->toBeTrue()
        ->and(User::query()->where('email', 'jane@example.com')->exists())->toBeTrue();
});

test('insert or ignore with duplicate data', function (): void {
    $manager = app(UserModelManager::class);
    User::factory()->create(['email' => 'existing@example.com']);

    $manager->insertOrIgnore([
        ['name' => 'New User', 'email' => 'existing@example.com', 'password' => 'password123'],
        ['name' => 'Another User', 'email' => 'new@example.com', 'password' => 'password456'],
    ]);

    expect(User::count())->toBe(2)
        ->and(User::query()->where('email', 'new@example.com')->exists())->toBeTrue();
});

test('update or create creates new record', function (): void {
    $manager = app(UserModelManager::class);
    $result = $manager->updateOrCreate(
        ['email' => 'newuser@example.com'],
        ['name' => 'New User', 'password' => 'password123'],
    );

    expect(User::query()->where('email', 'newuser@example.com')->exists())->toBeTrue()
        ->and($result)->toBeInstanceOf(User::class)
        ->and($result->email)->toBe('newuser@example.com')
        ->and($result->name)->toBe('New User');
});

test('update or create updates existing record', function (): void {
    $manager = app(UserModelManager::class);
    $existingUser = User::factory()->create([
        'email' => 'existing@example.com',
        'name' => 'Original Name',
    ]);

    $result = $manager->updateOrCreate(
        ['email' => 'existing@example.com'],
        ['name' => 'Updated Name'],
    );

    expect($result->id)->toBe($existingUser->id)
        ->and($result->name)->toBe('Updated Name');
});

test('update or create with only attributes', function (): void {
    $manager = app(UserModelManager::class);
    $result = $manager->updateOrCreate([
        'email' => 'test@example.com',
        'name' => 'Test User',
        'password' => 'password123',
    ]);

    expect(User::query()->where('email', 'test@example.com')->exists())->toBeTrue()
        ->and($result->email)->toBe('test@example.com');
});

test('update or create updates with empty values', function (): void {
    $manager = app(UserModelManager::class);
    $existingUser = User::factory()->create([
        'email' => 'existing@example.com',
        'name' => 'Original Name',
    ]);

    $result = $manager->updateOrCreate(
        ['email' => 'existing@example.com'],
        [],
    );

    expect($result->id)->toBe($existingUser->id);
    
    $existingUser->refresh();
    expect($existingUser->name)->toBe('Original Name');
});

test('get or create creates new record', function (): void {
    $manager = app(UserModelManager::class);
    $result = $manager->getOrCreate(
        ['email' => 'newuser@example.com'],
        ['name' => 'New User', 'password' => 'password123'],
    );

    expect(User::query()->where('email', 'newuser@example.com')->exists())->toBeTrue()
        ->and($result)->toBeInstanceOf(User::class)
        ->and($result->email)->toBe('newuser@example.com')
        ->and($result->name)->toBe('New User');
});

test('get or create returns existing record', function (): void {
    $manager = app(UserModelManager::class);
    $existingUser = User::factory()->create([
        'email' => 'existing@example.com',
        'name' => 'Original Name',
    ]);

    $result = $manager->getOrCreate(
        ['email' => 'existing@example.com'],
        ['name' => 'Updated Name'],
    );

    expect($result->id)->toBe($existingUser->id);
    
    $existingUser->refresh();
    expect($existingUser->name)->toBe('Original Name')
        ->and($result->name)->toBe('Original Name');
});

test('get or create with only attributes', function (): void {
    $manager = app(UserModelManager::class);
    $result = $manager->getOrCreate([
        'email' => 'test@example.com',
        'name' => 'Test User',
        'password' => 'password123',
    ]);

    expect(User::query()->where('email', 'test@example.com')->exists())->toBeTrue()
        ->and($result->email)->toBe('test@example.com')
        ->and($result->name)->toBe('Test User');
});

test('get or create returns existing with empty values', function (): void {
    $manager = app(UserModelManager::class);
    $existingUser = User::factory()->create([
        'email' => 'existing@example.com',
        'name' => 'Original Name',
    ]);

    $result = $manager->getOrCreate(
        ['email' => 'existing@example.com'],
        [],
    );

    expect($result->id)->toBe($existingUser->id);
    
    $existingUser->refresh();
    expect($existingUser->name)->toBe('Original Name')
        ->and($result->name)->toBe('Original Name');
});

test('raw mass update with empty data returns zero', function (): void {
    $manager = app(UserModelManager::class);
    $result = $manager->rawMassUpdate([]);

    expect($result)->toBe(0);
});

test('raw mass update with array data', function (): void {
    $manager = app(UserModelManager::class);
    $user1 = User::factory()->create(['name' => 'John Doe', 'email' => 'john@example.com']);
    $user2 = User::factory()->create(['name' => 'Jane Smith', 'email' => 'jane@example.com']);

    $result = $manager->rawMassUpdate([
        ['id' => $user1->id, 'name' => 'John Updated'],
        ['id' => $user2->id, 'name' => 'Jane Updated'],
    ]);

    expect($result)->toBe(2);
    
    $user1->refresh();
    $user2->refresh();
    expect($user1->name)->toBe('John Updated')
        ->and($user2->name)->toBe('Jane Updated');
});

test('raw mass update with custom unique by', function (): void {
    $manager = app(UserModelManager::class);
    $user1 = User::factory()->create(['name' => 'John Doe', 'email' => 'john@example.com']);
    $user2 = User::factory()->create(['name' => 'Jane Smith', 'email' => 'jane@example.com']);

    $result = $manager->rawMassUpdate([
        ['email' => 'john@example.com', 'name' => 'John Updated'],
        ['email' => 'jane@example.com', 'name' => 'Jane Updated'],
    ], 'email');

    expect($result)->toBe(2);
    
    $user1->refresh();
    $user2->refresh();
    expect($user1->name)->toBe('John Updated')
        ->and($user2->name)->toBe('Jane Updated');
});

test('raw mass update with model instances', function (): void {
    $manager = app(UserModelManager::class);
    $user1 = User::factory()->create(['name' => 'John Doe', 'email' => 'john@example.com']);
    $user2 = User::factory()->create(['name' => 'Jane Smith', 'email' => 'jane@example.com']);

    $user1->name = 'John Updated';
    $user2->name = 'Jane Updated';

    $result = $manager->rawMassUpdate([$user1, $user2]);

    expect($result)->toBe(2);
    
    $user1->refresh();
    $user2->refresh();
    expect($user1->name)->toBe('John Updated')
        ->and($user2->name)->toBe('Jane Updated');
});

test('raw mass update with multiple columns', function (): void {
    $manager = app(UserModelManager::class);
    $user1 = User::factory()->create(['name' => 'John Doe', 'email' => 'john@example.com']);
    $user2 = User::factory()->create(['name' => 'Jane Smith', 'email' => 'jane@example.com']);

    $result = $manager->rawMassUpdate([
        ['id' => $user1->id, 'name' => 'John Updated', 'email' => 'john.updated@example.com'],
        ['id' => $user2->id, 'name' => 'Jane Updated', 'email' => 'jane.updated@example.com'],
    ]);

    expect($result)->toBe(2);
    
    $user1->refresh();
    $user2->refresh();
    expect($user1->name)->toBe('John Updated')
        ->and($user1->email)->toBe('john.updated@example.com')
        ->and($user2->name)->toBe('Jane Updated')
        ->and($user2->email)->toBe('jane.updated@example.com');
});

test('raw mass update with array unique by', function (): void {
    $manager = app(UserModelManager::class);
    $user1 = User::factory()->create(['name' => 'John Doe', 'email' => 'john@example.com']);
    $user2 = User::factory()->create(['name' => 'Jane Smith', 'email' => 'jane@example.com']);

    $result = $manager->rawMassUpdate([
        ['name' => 'John Doe', 'email' => 'john@example.com', 'password' => 'newpass123'],
        ['name' => 'Jane Smith', 'email' => 'jane@example.com', 'password' => 'newpass456'],
    ], ['name', 'email']);

    expect($result)->toBe(2);
    
    $user1->refresh();
    $user2->refresh();
    expect($user1->password)->toBe('newpass123')
        ->and($user2->password)->toBe('newpass456');
});

test('raw mass update throws exception when trait not used', function (): void {
    $modelManager = new UserWithoutMassUpdateModelManager();
    User::factory()->create();

    $modelManager->rawMassUpdate([
        ['id' => 1, 'name' => 'John Updated'],
    ]);
})->throws(MassUpdateNotAvailable::class, 'must use the MassUpdatable trait');
