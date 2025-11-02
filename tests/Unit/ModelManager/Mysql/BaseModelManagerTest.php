<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests\Unit\ModelManager\Mysql;

use Pekral\Arch\Examples\Services\User\UserModelManager;
use Pekral\Arch\Tests\Models\User;
use Pekral\Arch\Tests\TestCase;

final class BaseModelManagerTest extends TestCase
{

    private UserModelManager $userModelManager;

    public function testBulkUpdateWithMissingKeyColumn(): void
    {
        // Arrange
        $data = [
            ['name' => 'Updated User 1'],
            ['name' => 'Updated User 2'],
        ];
        
        // Act
        $result = $this->userModelManager->bulkUpdate($data);
        
        // Assert
        $this->assertSame(0, $result);
    }

    public function testBulkUpdateWithEmptyDataAfterRemovingKey(): void
    {
        // Arrange
        $user = User::factory()->create(['name' => 'Original Name']);
        $data = [
            ['id' => $user->id],
        ];
        
        // Act
        $result = $this->userModelManager->bulkUpdate($data);
        
        // Assert
        $this->assertSame(0, $result);
    }

    public function testInsertOrIgnoreWithEmptyData(): void
    {
        // Arrange
        $data = [];

        // Act & Assert - should not throw exception
        $this->userModelManager->insertOrIgnore($data);

        // No exception thrown, test passes
    }

    public function testInsertOrIgnoreWithValidData(): void
    {
        // Arrange
        $data = [
            ['name' => 'John Doe', 'email' => 'john@example.com', 'password' => 'password123'],
            ['name' => 'Jane Smith', 'email' => 'jane@example.com', 'password' => 'password456'],
        ];
        
        // Act
        $this->userModelManager->insertOrIgnore($data);
        
        // Assert
        $this->assertDatabaseHas('users', ['name' => 'John Doe', 'email' => 'john@example.com']);
        $this->assertDatabaseHas('users', ['name' => 'Jane Smith', 'email' => 'jane@example.com']);
    }

    public function testInsertOrIgnoreWithDuplicateData(): void
    {
        // Arrange
        User::factory()->create(['email' => 'existing@example.com']);
        $data = [
            ['name' => 'New User', 'email' => 'existing@example.com', 'password' => 'password123'],
            ['name' => 'Another User', 'email' => 'new@example.com', 'password' => 'password456'],
        ];
        
        // Act
        $this->userModelManager->insertOrIgnore($data);
        
        // Assert
        $this->assertDatabaseCount('users', 2);
        $this->assertDatabaseHas('users', ['name' => 'Another User', 'email' => 'new@example.com']);
    }

    public function testUpdateOrCreateCreatesNewRecord(): void
    {
        // Arrange
        $attributes = ['email' => 'newuser@example.com'];
        $values = ['name' => 'New User', 'password' => 'password123'];
        
        // Act
        $result = $this->userModelManager->updateOrCreate($attributes, $values);
        
        // Assert
        $this->assertDatabaseHas('users', [
            'email' => 'newuser@example.com',
            'name' => 'New User',
        ]);
        $this->assertInstanceOf(User::class, $result);
        $this->assertEquals('newuser@example.com', $result->email);
        $this->assertEquals('New User', $result->name);
    }

    public function testUpdateOrCreateUpdatesExistingRecord(): void
    {
        // Arrange
        $existingUser = User::factory()->create([
            'email' => 'existing@example.com',
            'name' => 'Original Name',
        ]);
        $attributes = ['email' => 'existing@example.com'];
        $values = ['name' => 'Updated Name'];
        
        // Act
        $result = $this->userModelManager->updateOrCreate($attributes, $values);
        
        // Assert
        $this->assertSame($existingUser->id, $result->id);
        $this->assertEquals('existing@example.com', $result->email);
        $this->assertEquals('Updated Name', $result->name);
        $existingUser->refresh();
        $this->assertEquals('Updated Name', $existingUser->name);
    }

    public function testUpdateOrCreateWithOnlyAttributes(): void
    {
        // Arrange
        $attributes = [
            'email' => 'test@example.com',
            'name' => 'Test User',
            'password' => 'password123',
        ];
        
        // Act
        $result = $this->userModelManager->updateOrCreate($attributes);
        
        // Assert
        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
            'name' => 'Test User',
        ]);
        $this->assertEquals('test@example.com', $result->email);
        $this->assertEquals('Test User', $result->name);
    }

    public function testUpdateOrCreateUpdatesWithEmptyValues(): void
    {
        // Arrange
        $existingUser = User::factory()->create([
            'email' => 'existing@example.com',
            'name' => 'Original Name',
        ]);
        $attributes = ['email' => 'existing@example.com'];
        $values = [];
        
        // Act
        $result = $this->userModelManager->updateOrCreate($attributes, $values);
        
        // Assert
        $this->assertSame($existingUser->id, $result->id);
        $this->assertEquals('existing@example.com', $result->email);
        $existingUser->refresh();
        $this->assertEquals('Original Name', $existingUser->name);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->userModelManager = app(UserModelManager::class);
    }

}
