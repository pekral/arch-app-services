<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests\Unit\Actions\User;

use Illuminate\Validation\ValidationException;
use Pekral\Arch\Examples\Actions\User\GetOrCreateUser;
use Pekral\Arch\Tests\Models\User;
use Pekral\Arch\Tests\TestCase;

use function assert;
use function fake;

final class GetOrCreateUserTest extends TestCase
{

    public function testGetOrCreateUserCreatesNewRecord(): void
    {
        // Arrange
        $getOrCreateUserAction = $this->app?->make(GetOrCreateUser::class);
        assert($getOrCreateUserAction instanceof GetOrCreateUser);
        $attributes = ['email' => 'newuser@example.com'];
        $values = ['name' => 'New User', 'password' => 'password123'];
        
        // Act
        $result = $getOrCreateUserAction->execute($attributes, $values);
        
        // Assert
        $this->assertInstanceOf(User::class, $result);
        $this->assertEquals('newuser@example.com', $result->email);
        $this->assertEquals('New user', $result->name);
        $this->assertDatabaseHas('users', [
            'email' => 'newuser@example.com',
            'name' => 'New user',
        ]);
    }

    public function testGetOrCreateUserReturnsExistingRecord(): void
    {
        // Arrange
        $existingUser = User::factory()->create([
            'email' => 'existing@example.com',
            'name' => 'Original Name',
        ]);
        $getOrCreateUserAction = $this->app?->make(GetOrCreateUser::class);
        assert($getOrCreateUserAction instanceof GetOrCreateUser);
        $attributes = ['email' => 'existing@example.com'];
        $values = ['name' => 'Updated Name'];
        
        // Act
        $result = $getOrCreateUserAction->execute($attributes, $values);
        
        // Assert
        $this->assertSame($existingUser->id, $result->id);
        $this->assertEquals('existing@example.com', $result->email);
        $this->assertEquals('Original Name', $result->name);
        $existingUser->refresh();
        $this->assertEquals('Original Name', $existingUser->name);
    }

    public function testGetOrCreateUserWithInvalidEmail(): void
    {
        // Arrange
        $getOrCreateUserAction = $this->app?->make(GetOrCreateUser::class);
        assert($getOrCreateUserAction instanceof GetOrCreateUser);
        $attributes = ['email' => 'invalid-email'];
        $values = ['name' => 'Test Name'];
        
        // Act & Assert
        $this->expectException(ValidationException::class);
        $getOrCreateUserAction->execute($attributes, $values);
    }

    public function testGetOrCreateUserWithMissingEmail(): void
    {
        // Arrange
        $getOrCreateUserAction = $this->app?->make(GetOrCreateUser::class);
        assert($getOrCreateUserAction instanceof GetOrCreateUser);
        $attributes = [];
        $values = ['name' => 'Test Name'];
        
        // Act & Assert
        $this->expectException(ValidationException::class);
        $getOrCreateUserAction->execute($attributes, $values);
    }

    public function testGetOrCreateUserWithMissingName(): void
    {
        // Arrange
        $getOrCreateUserAction = $this->app?->make(GetOrCreateUser::class);
        assert($getOrCreateUserAction instanceof GetOrCreateUser);
        $attributes = ['email' => fake()->email()];
        $values = [];
        
        // Act & Assert
        $this->expectException(ValidationException::class);
        $getOrCreateUserAction->execute($attributes, $values);
    }

    public function testGetOrCreateUserTransformsEmailToLowercase(): void
    {
        // Arrange
        $getOrCreateUserAction = $this->app?->make(GetOrCreateUser::class);
        assert($getOrCreateUserAction instanceof GetOrCreateUser);
        $attributes = ['email' => 'UPPERCASE@EXAMPLE.COM'];
        $values = ['name' => 'Test', 'password' => 'password123'];
        
        // Act
        $result = $getOrCreateUserAction->execute($attributes, $values);
        
        // Assert
        $this->assertEquals('uppercase@example.com', $result->email);
        $this->assertDatabaseHas('users', ['email' => 'uppercase@example.com']);
    }

    public function testGetOrCreateUserTransformsNameToUcfirst(): void
    {
        // Arrange
        $getOrCreateUserAction = $this->app?->make(GetOrCreateUser::class);
        assert($getOrCreateUserAction instanceof GetOrCreateUser);
        $attributes = ['email' => fake()->email()];
        $values = ['name' => 'lowercase name', 'password' => 'password123'];
        
        // Act
        $result = $getOrCreateUserAction->execute($attributes, $values);
        
        // Assert
        $this->assertEquals('Lowercase name', $result->name);
    }

    public function testGetOrCreateUserWithEmailInValues(): void
    {
        // Arrange
        $getOrCreateUserAction = $this->app?->make(GetOrCreateUser::class);
        assert($getOrCreateUserAction instanceof GetOrCreateUser);
        $attributes = ['email' => 'test@example.com'];
        $values = ['email' => 'UPDATED@EXAMPLE.COM', 'name' => 'Test User', 'password' => 'password123'];
        
        // Act
        $result = $getOrCreateUserAction->execute($attributes, $values);
        
        // Assert
        $this->assertEquals('updated@example.com', $result->email);
        $this->assertEquals('Test user', $result->name);
        $this->assertDatabaseHas('users', [
            'email' => 'updated@example.com',
            'name' => 'Test user',
        ]);
    }

    public function testGetOrCreateUserWithOnlyAttributes(): void
    {
        // Arrange
        $getOrCreateUserAction = $this->app?->make(GetOrCreateUser::class);
        assert($getOrCreateUserAction instanceof GetOrCreateUser);
        $attributes = [
            'email' => 'test@example.com',
            'name' => 'test user',
            'password' => 'password123',
        ];
        $values = [];
        
        // Act
        $result = $getOrCreateUserAction->execute($attributes, $values);
        
        // Assert
        $this->assertEquals('test@example.com', $result->email);
        $this->assertEquals('test user', $result->name);
    }

    public function testGetOrCreateUserReturnsExistingWithEmptyValues(): void
    {
        // Arrange
        $existingUser = User::factory()->create([
            'email' => 'existing@example.com',
            'name' => 'Original Name',
        ]);
        $getOrCreateUserAction = $this->app?->make(GetOrCreateUser::class);
        assert($getOrCreateUserAction instanceof GetOrCreateUser);
        $attributes = [
            'email' => 'existing@example.com',
            'name' => 'Original Name',
        ];
        $values = [];
        
        // Act
        $result = $getOrCreateUserAction->execute($attributes, $values);
        
        // Assert
        $this->assertSame($existingUser->id, $result->id);
        $existingUser->refresh();
        $this->assertEquals('Original Name', $existingUser->name);
        $this->assertEquals('Original Name', $result->name);
    }

}
