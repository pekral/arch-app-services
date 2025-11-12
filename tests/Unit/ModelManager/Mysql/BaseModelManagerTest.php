<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests\Unit\ModelManager\Mysql;

use Pekral\Arch\Examples\Services\User\UserModelManager;
use Pekral\Arch\Exceptions\MassUpdateNotAvailable;
use Pekral\Arch\Tests\Models\User;
use Pekral\Arch\Tests\TestCase;
use Pekral\Arch\Tests\Unit\ModelManager\UserWithoutMassUpdateModelManager;

final class BaseModelManagerTest extends TestCase
{

    public function testBulkUpdateWithMissingKeyColumn(): void
    {
        $manager = app(UserModelManager::class);

        $result = $manager->bulkUpdate([
            ['name' => 'Updated User 1'],
            ['name' => 'Updated User 2'],
        ]);

        $this->assertSame(0, $result);
    }

    public function testBulkUpdateWithEmptyDataAfterRemovingKey(): void
    {
        $manager = app(UserModelManager::class);
        $user = User::factory()->create();

        $result = $manager->bulkUpdate([
            ['id' => $user->id],
        ]);

        $this->assertSame(0, $result);
    }

    public function testInsertOrIgnoreWithEmptyData(): void
    {
        $manager = app(UserModelManager::class);

        $manager->insertOrIgnore([]);
    }

    public function testInsertOrIgnoreWithValidData(): void
    {
        $manager = app(UserModelManager::class);

        $manager->insertOrIgnore([
            ['name' => 'John Doe', 'email' => 'john@example.com', 'password' => 'password123'],
            ['name' => 'Jane Smith', 'email' => 'jane@example.com', 'password' => 'password456'],
        ]);

        $this->assertDatabaseHas('users', ['email' => 'john@example.com']);
        $this->assertDatabaseHas('users', ['email' => 'jane@example.com']);
    }

    public function testInsertOrIgnoreWithDuplicateData(): void
    {
        $manager = app(UserModelManager::class);
        User::factory()->create(['email' => 'existing@example.com']);

        $manager->insertOrIgnore([
            ['name' => 'New User', 'email' => 'existing@example.com', 'password' => 'password123'],
            ['name' => 'Another User', 'email' => 'new@example.com', 'password' => 'password456'],
        ]);

        $this->assertDatabaseCount('users', 2);
        $this->assertDatabaseHas('users', ['email' => 'new@example.com']);
    }

    public function testUpdateOrCreateCreatesNewRecord(): void
    {
        $manager = app(UserModelManager::class);

        $result = $manager->updateOrCreate(
            ['email' => 'newuser@example.com'],
            ['name' => 'New User', 'password' => 'password123'],
        );

        $this->assertDatabaseHas('users', ['email' => 'newuser@example.com']);
        $this->assertInstanceOf(User::class, $result);
        $this->assertEquals('newuser@example.com', $result->email);
        $this->assertEquals('New User', $result->name);
    }

    public function testUpdateOrCreateUpdatesExistingRecord(): void
    {
        $manager = app(UserModelManager::class);
        $existingUser = User::factory()->create([
            'email' => 'existing@example.com',
            'name' => 'Original Name',
        ]);

        $result = $manager->updateOrCreate(
            ['email' => 'existing@example.com'],
            ['name' => 'Updated Name'],
        );

        $this->assertSame($existingUser->id, $result->id);
        $this->assertEquals('Updated Name', $result->name);
    }

    public function testUpdateOrCreateWithOnlyAttributes(): void
    {
        $manager = app(UserModelManager::class);

        $result = $manager->updateOrCreate([
            'email' => 'test@example.com',
            'name' => 'Test User',
            'password' => 'password123',
        ]);

        $this->assertDatabaseHas('users', ['email' => 'test@example.com']);
        $this->assertEquals('test@example.com', $result->email);
    }

    public function testUpdateOrCreateUpdatesWithEmptyValues(): void
    {
        $manager = app(UserModelManager::class);
        $existingUser = User::factory()->create([
            'email' => 'existing@example.com',
            'name' => 'Original Name',
        ]);

        $result = $manager->updateOrCreate(
            ['email' => 'existing@example.com'],
            [],
        );

        $this->assertSame($existingUser->id, $result->id);
        $existingUser->refresh();
        $this->assertEquals('Original Name', $existingUser->name);
    }

    public function testGetOrCreateCreatesNewRecord(): void
    {
        $manager = app(UserModelManager::class);

        $result = $manager->getOrCreate(
            ['email' => 'newuser@example.com'],
            ['name' => 'New User', 'password' => 'password123'],
        );

        $this->assertDatabaseHas('users', ['email' => 'newuser@example.com']);
        $this->assertInstanceOf(User::class, $result);
        $this->assertEquals('newuser@example.com', $result->email);
        $this->assertEquals('New User', $result->name);
    }

    public function testGetOrCreateReturnsExistingRecord(): void
    {
        $manager = app(UserModelManager::class);
        $existingUser = User::factory()->create([
            'email' => 'existing@example.com',
            'name' => 'Original Name',
        ]);

        $result = $manager->getOrCreate(
            ['email' => 'existing@example.com'],
            ['name' => 'Updated Name'],
        );

        $this->assertSame($existingUser->id, $result->id);
        $existingUser->refresh();
        $this->assertEquals('Original Name', $existingUser->name);
        $this->assertEquals('Original Name', $result->name);
    }

    public function testGetOrCreateWithOnlyAttributes(): void
    {
        $manager = app(UserModelManager::class);

        $result = $manager->getOrCreate([
            'email' => 'test@example.com',
            'name' => 'Test User',
            'password' => 'password123',
        ]);

        $this->assertDatabaseHas('users', ['email' => 'test@example.com']);
        $this->assertEquals('test@example.com', $result->email);
        $this->assertEquals('Test User', $result->name);
    }

    public function testGetOrCreateReturnsExistingWithEmptyValues(): void
    {
        $manager = app(UserModelManager::class);
        $existingUser = User::factory()->create([
            'email' => 'existing@example.com',
            'name' => 'Original Name',
        ]);

        $result = $manager->getOrCreate(
            ['email' => 'existing@example.com'],
            [],
        );

        $this->assertSame($existingUser->id, $result->id);
        $existingUser->refresh();
        $this->assertEquals('Original Name', $existingUser->name);
        $this->assertEquals('Original Name', $result->name);
    }

    public function testRawMassUpdateWithEmptyData(): void
    {
        $manager = app(UserModelManager::class);

        $result = $manager->rawMassUpdate([]);

        $this->assertSame(0, $result);
    }

    public function testRawMassUpdateWithArrayData(): void
    {
        $manager = app(UserModelManager::class);
        [$user1, $user2] = $this->createTwoUsers();

        $result = $manager->rawMassUpdate([
            ['id' => $user1->id, 'name' => 'John Updated'],
            ['id' => $user2->id, 'name' => 'Jane Updated'],
        ]);

        $this->assertSame(2, $result);
        $this->assertUserNamesAre($user1, $user2, 'John Updated', 'Jane Updated');
    }

    public function testRawMassUpdateWithCustomUniqueBy(): void
    {
        $manager = app(UserModelManager::class);
        [$user1, $user2] = $this->createTwoUsers();

        $result = $manager->rawMassUpdate([
            ['email' => 'john@example.com', 'name' => 'John Updated'],
            ['email' => 'jane@example.com', 'name' => 'Jane Updated'],
        ], 'email');

        $this->assertSame(2, $result);
        $this->assertUserNamesAre($user1, $user2, 'John Updated', 'Jane Updated');
    }

    public function testRawMassUpdateWithModelInstances(): void
    {
        $manager = app(UserModelManager::class);
        [$user1, $user2] = $this->createTwoUsers();

        $user1->name = 'John Updated';
        $user2->name = 'Jane Updated';

        $result = $manager->rawMassUpdate([$user1, $user2]);

        $this->assertSame(2, $result);
        $this->assertUserNamesAre($user1, $user2, 'John Updated', 'Jane Updated');
    }

    public function testRawMassUpdateWithMultipleColumns(): void
    {
        $manager = app(UserModelManager::class);
        [$user1, $user2] = $this->createTwoUsers();

        $result = $manager->rawMassUpdate([
            ['id' => $user1->id, 'name' => 'John Updated', 'email' => 'john.updated@example.com'],
            ['id' => $user2->id, 'name' => 'Jane Updated', 'email' => 'jane.updated@example.com'],
        ]);

        $this->assertSame(2, $result);
        $user1->refresh();
        $user2->refresh();
        $this->assertEquals('John Updated', $user1->name);
        $this->assertEquals('john.updated@example.com', $user1->email);
        $this->assertEquals('Jane Updated', $user2->name);
        $this->assertEquals('jane.updated@example.com', $user2->email);
    }

    public function testRawMassUpdateWithArrayUniqueBy(): void
    {
        $manager = app(UserModelManager::class);
        [$user1, $user2] = $this->createTwoUsers();

        $result = $manager->rawMassUpdate([
            ['name' => 'John Doe', 'email' => 'john@example.com', 'password' => 'newpass123'],
            ['name' => 'Jane Smith', 'email' => 'jane@example.com', 'password' => 'newpass456'],
        ], ['name', 'email']);

        $this->assertSame(2, $result);
        $user1->refresh();
        $user2->refresh();
        $this->assertEquals('newpass123', $user1->password);
        $this->assertEquals('newpass456', $user2->password);
    }

    public function testRawMassUpdateThrowsExceptionWhenTraitNotUsed(): void
    {
        $modelManager = new UserWithoutMassUpdateModelManager();
        User::factory()->create();

        $this->expectException(MassUpdateNotAvailable::class);
        $this->expectExceptionMessage('must use the MassUpdatable trait');

        $modelManager->rawMassUpdate([
            ['id' => 1, 'name' => 'John Updated'],
        ]);
    }

    /**
     * @return array{\Pekral\Arch\Tests\Models\User, \Pekral\Arch\Tests\Models\User}
     */
    private function createTwoUsers(): array
    {
        return [
            User::factory()->create(['name' => 'John Doe', 'email' => 'john@example.com']),
            User::factory()->create(['name' => 'Jane Smith', 'email' => 'jane@example.com']),
        ];
    }

    private function assertUserNamesAre(User $user1, User $user2, string $expectedName1, string $expectedName2): void
    {
        $user1->refresh();
        $user2->refresh();
        $this->assertEquals($expectedName1, $user1->name);
        $this->assertEquals($expectedName2, $user2->name);
    }

}
