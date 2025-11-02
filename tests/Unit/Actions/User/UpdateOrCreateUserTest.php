<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests\Unit\Actions\User;

use Illuminate\Validation\ValidationException;
use Pekral\Arch\Examples\Actions\User\UpdateOrCreateUser;
use Pekral\Arch\Tests\Models\User;
use Pekral\Arch\Tests\TestCase;

use function assert;
use function fake;

final class UpdateOrCreateUserTest extends TestCase
{

    public function testUpdateOrCreateUserCreatesNewRecord(): void
    {
        // Arrange
        $updateOrCreateUserAction = $this->app?->make(UpdateOrCreateUser::class);
        assert($updateOrCreateUserAction instanceof UpdateOrCreateUser);
        $attributes = ['email' => 'newuser@example.com'];
        $values = ['name' => 'New User', 'password' => 'password123'];
        
        // Act
        $result = $updateOrCreateUserAction->execute($attributes, $values);
        
        // Assert
        $this->assertInstanceOf(User::class, $result);
        $this->assertEquals('newuser@example.com', $result->email);
        $this->assertEquals('New user', $result->name);
        $this->assertDatabaseHas('users', [
            'email' => 'newuser@example.com',
            'name' => 'New user',
        ]);
    }

    public function testUpdateOrCreateUserUpdatesExistingRecord(): void
    {
        // Arrange
        $existingUser = User::factory()->create([
            'email' => 'existing@example.com',
            'name' => 'Original Name',
        ]);
        $updateOrCreateUserAction = $this->app?->make(UpdateOrCreateUser::class);
        assert($updateOrCreateUserAction instanceof UpdateOrCreateUser);
        $attributes = ['email' => 'existing@example.com'];
        $values = ['name' => 'Updated Name'];
        
        // Act
        $result = $updateOrCreateUserAction->execute($attributes, $values);
        
        // Assert
        $this->assertSame($existingUser->id, $result->id);
        $this->assertEquals('existing@example.com', $result->email);
        $this->assertEquals('Updated name', $result->name);
        $existingUser->refresh();
        $this->assertEquals('Updated name', $existingUser->name);
    }

    public function testUpdateOrCreateUserWithInvalidEmail(): void
    {
        // Arrange
        $updateOrCreateUserAction = $this->app?->make(UpdateOrCreateUser::class);
        assert($updateOrCreateUserAction instanceof UpdateOrCreateUser);
        $attributes = ['email' => 'invalid-email'];
        $values = ['name' => 'Test Name'];
        
        // Act & Assert
        $this->expectException(ValidationException::class);
        $updateOrCreateUserAction->execute($attributes, $values);
    }

    public function testUpdateOrCreateUserWithMissingEmail(): void
    {
        // Arrange
        $updateOrCreateUserAction = $this->app?->make(UpdateOrCreateUser::class);
        assert($updateOrCreateUserAction instanceof UpdateOrCreateUser);
        $attributes = [];
        $values = ['name' => 'Test Name'];
        
        // Act & Assert
        $this->expectException(ValidationException::class);
        $updateOrCreateUserAction->execute($attributes, $values);
    }

    public function testUpdateOrCreateUserWithMissingName(): void
    {
        // Arrange
        $updateOrCreateUserAction = $this->app?->make(UpdateOrCreateUser::class);
        assert($updateOrCreateUserAction instanceof UpdateOrCreateUser);
        $attributes = ['email' => fake()->email()];
        $values = [];
        
        // Act & Assert
        $this->expectException(ValidationException::class);
        $updateOrCreateUserAction->execute($attributes, $values);
    }

    public function testUpdateOrCreateUserTransformsEmailToLowercase(): void
    {
        // Arrange
        $updateOrCreateUserAction = $this->app?->make(UpdateOrCreateUser::class);
        assert($updateOrCreateUserAction instanceof UpdateOrCreateUser);
        $attributes = ['email' => 'UPPERCASE@EXAMPLE.COM'];
        $values = ['name' => 'Test', 'password' => 'password123'];
        
        // Act
        $result = $updateOrCreateUserAction->execute($attributes, $values);
        
        // Assert
        $this->assertEquals('uppercase@example.com', $result->email);
        $this->assertDatabaseHas('users', ['email' => 'uppercase@example.com']);
    }

    public function testUpdateOrCreateUserTransformsNameToUcfirst(): void
    {
        // Arrange
        $updateOrCreateUserAction = $this->app?->make(UpdateOrCreateUser::class);
        assert($updateOrCreateUserAction instanceof UpdateOrCreateUser);
        $attributes = ['email' => fake()->email()];
        $values = ['name' => 'lowercase name', 'password' => 'password123'];
        
        // Act
        $result = $updateOrCreateUserAction->execute($attributes, $values);
        
        // Assert
        $this->assertEquals('Lowercase name', $result->name);
    }

    public function testUpdateOrCreateUserWithEmailInValues(): void
    {
        // Arrange
        $updateOrCreateUserAction = $this->app?->make(UpdateOrCreateUser::class);
        assert($updateOrCreateUserAction instanceof UpdateOrCreateUser);
        $attributes = ['email' => 'test@example.com'];
        $values = ['email' => 'UPDATED@EXAMPLE.COM', 'name' => 'Test User', 'password' => 'password123'];
        
        // Act
        $result = $updateOrCreateUserAction->execute($attributes, $values);
        
        // Assert
        $this->assertEquals('updated@example.com', $result->email);
        $this->assertEquals('Test user', $result->name);
        $this->assertDatabaseHas('users', [
            'email' => 'updated@example.com',
            'name' => 'Test user',
        ]);
    }

    public function testUpdateOrCreateUserWithOnlyAttributes(): void
    {
        // Arrange
        $updateOrCreateUserAction = $this->app?->make(UpdateOrCreateUser::class);
        assert($updateOrCreateUserAction instanceof UpdateOrCreateUser);
        $attributes = [
            'email' => 'test@example.com',
            'name' => 'test user',
            'password' => 'password123',
        ];
        $values = [];
        
        // Act
        $result = $updateOrCreateUserAction->execute($attributes, $values);
        
        // Assert
        $this->assertEquals('test@example.com', $result->email);
        $this->assertEquals('test user', $result->name);
    }

}
