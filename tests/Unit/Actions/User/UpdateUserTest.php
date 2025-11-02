<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests\Unit\Actions\User;

use Illuminate\Validation\ValidationException;
use Pekral\Arch\Examples\Actions\User\UpdateUser;
use Pekral\Arch\Tests\Models\User;
use Pekral\Arch\Tests\TestCase;

use function assert;
use function fake;

final class UpdateUserTest extends TestCase
{

    public function testUpdateUserWithValidData(): void
    {
        // Arrange
        $user = User::factory()->create([
            'email' => 'OLD@EXAMPLE.COM',
            'name' => 'old name',
        ]);
        $updateUserAction = $this->app?->make(UpdateUser::class);
        assert($updateUserAction instanceof UpdateUser);
        $data = [
            'email' => 'NEW@EXAMPLE.COM',
            'name' => 'new name',
        ];

        // Act
        $result = $updateUserAction->execute($user, $data);

        // Assert
        $this->assertSame($user, $result);
        $user->refresh();
        $this->assertEquals('new@example.com', $user->email);
        $this->assertEquals('New name', $user->name);
    }

    public function testUpdateUserWithInvalidEmail(): void
    {
        // Arrange
        $user = User::factory()->create();
        $updateUserAction = $this->app?->make(UpdateUser::class);
        assert($updateUserAction instanceof UpdateUser);
        $data = [
            'email' => 'invalid-email',
            'name' => 'Test Name',
        ];

        // Act & Assert
        $this->expectException(ValidationException::class);
        $updateUserAction->execute($user, $data);
    }

    public function testUpdateUserWithMissingEmail(): void
    {
        // Arrange
        $user = User::factory()->create();
        $updateUserAction = $this->app?->make(UpdateUser::class);
        assert($updateUserAction instanceof UpdateUser);
        $data = [
            'name' => 'Test Name',
        ];

        // Act & Assert
        $this->expectException(ValidationException::class);
        $updateUserAction->execute($user, $data);
    }

    public function testUpdateUserWithMissingName(): void
    {
        // Arrange
        $user = User::factory()->create();
        $updateUserAction = $this->app?->make(UpdateUser::class);
        assert($updateUserAction instanceof UpdateUser);
        $data = [
            'email' => fake()->email(),
        ];

        // Act & Assert
        $this->expectException(ValidationException::class);
        $updateUserAction->execute($user, $data);
    }

    public function testUpdateUserTransformsEmailToLowercase(): void
    {
        // Arrange
        $user = User::factory()->create();
        $updateUserAction = $this->app?->make(UpdateUser::class);
        assert($updateUserAction instanceof UpdateUser);
        $data = [
            'email' => 'UPPERCASE@EXAMPLE.COM',
            'name' => 'Test',
        ];

        // Act
        $updateUserAction->execute($user, $data);

        // Assert
        $user->refresh();
        $this->assertEquals('uppercase@example.com', $user->email);
    }

    public function testUpdateUserTransformsNameToUcfirst(): void
    {
        // Arrange
        $user = User::factory()->create();
        $updateUserAction = $this->app?->make(UpdateUser::class);
        assert($updateUserAction instanceof UpdateUser);
        $data = [
            'email' => fake()->email(),
            'name' => 'lowercase name',
        ];

        // Act
        $updateUserAction->execute($user, $data);

        // Assert
        $user->refresh();
        $this->assertEquals('Lowercase name', $user->name);
    }

    public function testUpdateUserWithEmptyData(): void
    {
        // Arrange
        $user = User::factory()->create();
        $updateUserAction = $this->app?->make(UpdateUser::class);
        assert($updateUserAction instanceof UpdateUser);
        $data = [];

        // Act & Assert
        $this->expectException(ValidationException::class);
        $updateUserAction->execute($user, $data);
    }

}
