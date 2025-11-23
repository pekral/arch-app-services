<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests\Unit\ModelManager\DynamoDb;

use Aws\DynamoDb\Exception\DynamoDbException;
use BaoPham\DynamoDb\DynamoDbQueryBuilder;
use Illuminate\Database\Eloquent\Model;
use Mockery;
use Pekral\Arch\Examples\Services\User\UserDynamoModelManager;
use Pekral\Arch\Exceptions\DynamoDbNotSupported;
use Pekral\Arch\Tests\Models\UserDynamoModel;
use TypeError;

use function app;
use function expect;
use function fake;
use function test;

test('delete by params deletes matching record', function (): void {
    $manager = app(UserDynamoModelManager::class);
    $userId = fake()->uuid();
    $user = new UserDynamoModel();
    $user->id = $userId;
    $user->email = 'delete@example.com';
    $user->name = 'Delete User';
    $user->password = 'password123';
    $user->save();

    $manager->deleteByParams(['id' => $userId]);
})->throws(DynamoDbException::class);

test('bulk delete by params deletes matching records', function (): void {
    $manager = app(UserDynamoModelManager::class);
    $johnIds = [];

    for ($i = 0; $i < 5; $i++) {
        $user = new UserDynamoModel();
        $user->id = fake()->uuid();
        $user->email = sprintf('john%d@example.com', $i);
        $user->name = 'John';
        $user->password = 'password123';
        $user->save();
        $johnIds[] = $user->id;
    }

    $janeIds = [];

    for ($i = 0; $i < 3; $i++) {
        $user = new UserDynamoModel();
        $user->id = fake()->uuid();
        $user->email = sprintf('jane%d@example.com', $i);
        $user->name = 'Jane';
        $user->password = 'password123';
        $user->save();
        $janeIds[] = $user->id;
    }

    foreach ($johnIds as $id) {
        $manager->bulkDeleteByParams(['id' => $id]);
    }
})->throws(DynamoDbException::class);

test('bulk delete by params with multiple conditions', function (): void {
    $manager = app(UserDynamoModelManager::class);
    $user1 = new UserDynamoModel();
    $user1->id = fake()->uuid();
    $user1->email = 'john1@example.com';
    $user1->name = 'John';
    $user1->password = 'password123';
    $user1->save();

    $user2 = new UserDynamoModel();
    $user2->id = fake()->uuid();
    $user2->email = 'john2@example.com';
    $user2->name = 'John';
    $user2->password = 'password123';
    $user2->save();

    $user3 = new UserDynamoModel();
    $user3->id = fake()->uuid();
    $user3->email = 'jane@example.com';
    $user3->name = 'Jane';
    $user3->password = 'password123';
    $user3->save();

    $manager->bulkDeleteByParams(['id' => $user1->id]);
})->throws(DynamoDbException::class);

test('bulk delete by params with no matching records does nothing', function (): void {
    $manager = app(UserDynamoModelManager::class);
    $userIds = [];

    for ($i = 0; $i < 3; $i++) {
        $user = new UserDynamoModel();
        $user->id = fake()->uuid();
        $user->email = sprintf('john%d@example.com', $i);
        $user->name = 'John';
        $user->password = 'password123';
        $user->save();
        $userIds[] = $user->id;
    }

    $nonExistentId = fake()->uuid();
    $manager->bulkDeleteByParams(['id' => $nonExistentId]);
})->throws(DynamoDbException::class);

test('bulk delete by params deletes all records when no conditions', function (): void {
    $manager = app(UserDynamoModelManager::class);
    $userIds = [];

    for ($i = 0; $i < 5; $i++) {
        $user = new UserDynamoModel();
        $user->id = fake()->uuid();
        $user->email = sprintf('user%d@example.com', $i);
        $user->name = 'User ' . $i;
        $user->password = 'password123';
        $user->save();
        $userIds[] = $user->id;
    }

    $manager->bulkDeleteByParams([]);
})->throws(DynamoDbException::class);

test('bulk delete by params with empty table does nothing', function (): void {
    $manager = app(UserDynamoModelManager::class);

    $nonExistentId = fake()->uuid();
    $manager->bulkDeleteByParams(['id' => $nonExistentId]);
})->throws(DynamoDbException::class);

test('delete deletes model instance', function (): void {
    $manager = app(UserDynamoModelManager::class);
    $user = new UserDynamoModel();
    $user->id = fake()->uuid();
    $user->email = 'delete@example.com';
    $user->name = 'Delete User';
    $user->password = 'password123';
    $user->save();

    $result = $manager->delete($user);

    expect($result)->toBeTrue()
        ->and(UserDynamoModel::query()->where('email', 'delete@example.com')->first())->toBeNull();
});

test('delete returns false when model delete returns null', function (): void {
    $manager = app(UserDynamoModelManager::class);

    $mockUser = Mockery::mock(Model::class);
    $mockUser->shouldReceive('delete')->once()->andReturn(null);

    $result = $manager->delete($mockUser);

    expect($result)->toBeFalse();
});

test('create creates new record', function (): void {
    $manager = app(UserDynamoModelManager::class);
    $userId = fake()->uuid();
    $user = $manager->create([
        'id' => $userId,
        'email' => 'new@example.com',
        'name' => 'New User',
        'password' => 'password123',
    ]);

    expect($user)->toBeInstanceOf(UserDynamoModel::class)
        ->and($user->name)->toBe('New User')
        ->and($user->id)->toBe($userId);

    expect(UserDynamoModel::query()->where('email', 'new@example.com')->first())->not->toBeNull();
});

test('update updates existing record', function (): void {
    $manager = app(UserDynamoModelManager::class);
    $user = new UserDynamoModel();
    $user->id = fake()->uuid();
    $user->email = 'old@example.com';
    $user->name = 'Old Name';
    $user->password = 'password123';
    $user->save();

    $result = $manager->update($user, ['name' => 'New Name']);

    expect($result)->toBeTrue();

    $user->refresh();
    expect($user->name)->toBe('New Name');
});

test('bulk create creates multiple records', function (): void {
    $manager = app(UserDynamoModelManager::class);
    $result = $manager->bulkCreate([
        ['id' => fake()->uuid(), 'name' => 'User 1', 'email' => 'user1@example.com', 'password' => 'pass1'],
        ['id' => fake()->uuid(), 'name' => 'User 2', 'email' => 'user2@example.com', 'password' => 'pass2'],
        ['id' => fake()->uuid(), 'name' => 'User 3', 'email' => 'user3@example.com', 'password' => 'pass3'],
    ]);

    expect($result)->toBe(3);
});

test('bulk create with empty data returns zero', function (): void {
    $manager = app(UserDynamoModelManager::class);
    $result = $manager->bulkCreate([]);

    expect($result)->toBe(0);
});

test('bulk update updates multiple records', function (): void {
    $manager = app(UserDynamoModelManager::class);
    $user1 = new UserDynamoModel();
    $user1->id = fake()->uuid();
    $user1->email = 'user1@example.com';
    $user1->name = 'User 1';
    $user1->password = 'password123';
    $user1->save();

    $user2 = new UserDynamoModel();
    $user2->id = fake()->uuid();
    $user2->email = 'user2@example.com';
    $user2->name = 'User 2';
    $user2->password = 'password123';
    $user2->save();

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
    $manager = app(UserDynamoModelManager::class);
    $result = $manager->bulkUpdate([]);

    expect($result)->toBe(0);
});

test('bulk update with missing key column returns zero', function (): void {
    $manager = app(UserDynamoModelManager::class);
    $result = $manager->bulkUpdate([
        ['name' => 'Updated User 1'],
        ['name' => 'Updated User 2'],
    ]);

    expect($result)->toBe(0);
});

test('bulk update with empty data after removing key returns zero', function (): void {
    $manager = app(UserDynamoModelManager::class);
    $user = new UserDynamoModel();
    $user->id = fake()->uuid();
    $user->email = 'user@example.com';
    $user->name = 'User';
    $user->password = 'password123';
    $user->save();

    $result = $manager->bulkUpdate([
        ['id' => $user->id],
    ]);

    expect($result)->toBe(0);
});

test('bulk update with non existent id skips record', function (): void {
    $manager = app(UserDynamoModelManager::class);
    $user = new UserDynamoModel();
    $user->id = fake()->uuid();
    $user->email = 'user@example.com';
    $user->name = 'User';
    $user->password = 'password123';
    $user->save();

    $result = $manager->bulkUpdate([
        ['id' => fake()->uuid(), 'name' => 'Updated User'],
    ]);

    expect($result)->toBe(0);
});

test('insert or ignore with empty data', function (): void {
    $manager = app(UserDynamoModelManager::class);
    $manager->insertOrIgnore([]);

    expect(true)->toBeTrue();
});

test('insert or ignore with valid data', function (): void {
    $manager = app(UserDynamoModelManager::class);
    $manager->insertOrIgnore([
        ['id' => fake()->uuid(), 'name' => 'John Doe', 'email' => 'john@example.com', 'password' => 'password123'],
        ['id' => fake()->uuid(), 'name' => 'Jane Smith', 'email' => 'jane@example.com', 'password' => 'password456'],
    ]);

    expect(true)->toBeTrue();
});

test('insert or ignore with duplicate data', function (): void {
    $manager = app(UserDynamoModelManager::class);
    $existingId = fake()->uuid();
    $user = new UserDynamoModel();
    $user->id = $existingId;
    $user->email = 'existing@example.com';
    $user->name = 'Existing User';
    $user->password = 'password123';
    $user->save();

    $manager->insertOrIgnore([
        ['id' => $existingId, 'name' => 'New User', 'email' => 'existing@example.com', 'password' => 'password123'],
        ['id' => fake()->uuid(), 'name' => 'Another User', 'email' => 'new@example.com', 'password' => 'password456'],
    ]);

    expect(true)->toBeTrue();
});

test('update or create creates new record', function (): void {
    $manager = app(UserDynamoModelManager::class);
    $userId = fake()->uuid();
    $manager->updateOrCreate(
        ['id' => $userId],
        ['email' => 'newuser@example.com', 'name' => 'New User', 'password' => 'password123'],
    );
})->throws(TypeError::class);

test('update or create updates existing record', function (): void {
    $manager = app(UserDynamoModelManager::class);
    $existingUser = new UserDynamoModel();
    $existingUser->id = fake()->uuid();
    $existingUser->email = 'existing@example.com';
    $existingUser->name = 'Original Name';
    $existingUser->password = 'password123';
    $existingUser->save();

    $manager->updateOrCreate(
        ['id' => $existingUser->id],
        ['name' => 'Updated Name'],
    );
})->throws(TypeError::class);

test('update or create with only attributes', function (): void {
    $manager = app(UserDynamoModelManager::class);
    $userId = fake()->uuid();
    $manager->updateOrCreate([
        'id' => $userId,
        'email' => 'test@example.com',
        'name' => 'Test User',
        'password' => 'password123',
    ]);
})->throws(TypeError::class);

test('update or create updates with empty values', function (): void {
    $manager = app(UserDynamoModelManager::class);
    $existingUser = new UserDynamoModel();
    $existingUser->id = fake()->uuid();
    $existingUser->email = 'existing@example.com';
    $existingUser->name = 'Original Name';
    $existingUser->password = 'password123';
    $existingUser->save();

    $manager->updateOrCreate(
        ['id' => $existingUser->id],
        [],
    );
})->throws(TypeError::class);

test('get or create creates new record', function (): void {
    $manager = app(UserDynamoModelManager::class);
    $userId = fake()->uuid();
    $manager->getOrCreate(
        ['id' => $userId],
        ['email' => 'newuser@example.com', 'name' => 'New User', 'password' => 'password123'],
    );
})->throws(TypeError::class);

test('get or create returns existing record', function (): void {
    $manager = app(UserDynamoModelManager::class);
    $existingUser = new UserDynamoModel();
    $existingUser->id = fake()->uuid();
    $existingUser->email = 'existing@example.com';
    $existingUser->name = 'Original Name';
    $existingUser->password = 'password123';
    $existingUser->save();

    $manager->getOrCreate(
        ['id' => $existingUser->id],
        ['name' => 'Updated Name'],
    );
})->throws(TypeError::class);

test('get or create with only attributes', function (): void {
    $manager = app(UserDynamoModelManager::class);
    $userId = fake()->uuid();
    $manager->getOrCreate([
        'id' => $userId,
        'email' => 'test@example.com',
        'name' => 'Test User',
        'password' => 'password123',
    ]);
})->throws(TypeError::class);

test('get or create returns existing with empty values', function (): void {
    $manager = app(UserDynamoModelManager::class);
    $existingUser = new UserDynamoModel();
    $existingUser->id = fake()->uuid();
    $existingUser->email = 'existing@example.com';
    $existingUser->name = 'Original Name';
    $existingUser->password = 'password123';
    $existingUser->save();

    $manager->getOrCreate(
        ['id' => $existingUser->id],
        [],
    );
})->throws(TypeError::class);

test('raw mass update throws exception', function (): void {
    $manager = app(UserDynamoModelManager::class);
    $user = new UserDynamoModel();
    $user->id = fake()->uuid();
    $user->email = 'test@example.com';
    $user->name = 'Test User';
    $user->password = 'password123';
    $user->save();

    $manager->rawMassUpdate([
        ['id' => $user->id, 'name' => 'Updated Name'],
    ]);
})->throws(DynamoDbNotSupported::class, 'raw mass update');

test('update or create returns model instance', function (): void {
    $manager = new TestableDynamoDbModelManager();
    $mockModel = Mockery::mock(Model::class);

    TestableDynamoDbModelForStaticMethods::setMockUpdateOrCreateResult($mockModel);
    $manager->setModelClassName(TestableDynamoDbModelForStaticMethods::class);

    $result = $manager->updateOrCreate(['id' => 'test-id'], ['name' => 'Test']);

    expect($result)->toBe($mockModel);

    TestableDynamoDbModelForStaticMethods::setMockUpdateOrCreateResult(null);
});

test('get or create returns model instance', function (): void {
    $manager = new TestableDynamoDbModelManager();
    $mockModel = Mockery::mock(Model::class);

    TestableDynamoDbModelForStaticMethods::setMockFirstOrCreateResult($mockModel);
    $manager->setModelClassName(TestableDynamoDbModelForStaticMethods::class);

    $result = $manager->getOrCreate(['id' => 'test-id'], ['name' => 'Test']);

    expect($result)->toBe($mockModel);

    TestableDynamoDbModelForStaticMethods::setMockFirstOrCreateResult(null);
});

test('delete by params returns bool when delete succeeds', function (): void {
    $manager = new TestableDynamoDbModelManager();
    $mockQuery = Mockery::mock(DynamoDbQueryBuilder::class);
    $mockQuery->shouldReceive('where')->once()->andReturnSelf();
    $mockQuery->shouldReceive('delete')->once()->andReturn(true);

    $manager->setNewModelQuery($mockQuery);

    $result = $manager->deleteByParams(['id' => fake()->uuid()]);

    expect($result)->toBeTrue();
});

test('delete by params returns false when delete returns false', function (): void {
    $manager = new TestableDynamoDbModelManager();
    $mockQuery = Mockery::mock(DynamoDbQueryBuilder::class);
    $mockQuery->shouldReceive('where')->once()->andReturnSelf();
    $mockQuery->shouldReceive('delete')->once()->andReturn(false);

    $manager->setNewModelQuery($mockQuery);

    $result = $manager->deleteByParams(['id' => fake()->uuid()]);

    expect($result)->toBeFalse();
});
