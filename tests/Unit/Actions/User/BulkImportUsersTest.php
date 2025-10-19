<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests\Unit\Actions\User;

use Pekral\Arch\Examples\Actions\User\BulkImportUsers;
use Pekral\Arch\Examples\Actions\User\BulkOperationsDemo;
use Pekral\Arch\Tests\Models\User;
use Pekral\Arch\Tests\TestCase;

final class BulkImportUsersTest extends TestCase
{

    public function testExecuteWithEmptyData(): void
    {
        // Arrange
        $action = app(BulkImportUsers::class);
        $userData = [];

        // Act
        $result = $action->execute($userData);

        // Assert
        $this->assertSame([
            'created' => 0,
            'ignored' => 0,
            'total_processed' => 0,
        ], $result);
    }

    public function testExecuteWithNewUsers(): void
    {
        // Arrange
        $action = app(BulkImportUsers::class);
        $userData = [
            ['name' => 'John Doe', 'email' => 'john@example.com', 'password' => 'password123'],
            ['name' => 'Jane Smith', 'email' => 'jane@example.com', 'password' => 'password456'],
        ];

        // Act
        $result = $action->execute($userData);

        // Assert
        $this->assertSame(2, $result['total_processed']);
        $this->assertSame(2, $result['created']);
        $this->assertSame(0, $result['ignored']);
        $this->assertDatabaseHas('users', ['name' => 'John Doe', 'email' => 'john@example.com']);
        $this->assertDatabaseHas('users', ['name' => 'Jane Smith', 'email' => 'jane@example.com']);
    }

    public function testExecuteWithMixedData(): void
    {
        // Arrange
        User::factory()->create(['email' => 'existing@example.com']);
        $action = app(BulkImportUsers::class);
        $userData = [
            ['name' => 'Existing User', 'email' => 'existing@example.com', 'password' => 'password123'],
            ['name' => 'New User', 'email' => 'new@example.com', 'password' => 'password456'],
        ];

        // Act
        $result = $action->execute($userData);

        // Assert
        $this->assertSame(2, $result['total_processed']);
        $this->assertSame(1, $result['created']);
        $this->assertSame(1, $result['ignored']);
        $this->assertDatabaseHas('users', ['name' => 'New User', 'email' => 'new@example.com']);
    }

}

final class BulkOperationsDemoTest extends TestCase
{

    public function testExecute(): void
    {
        // Arrange
        $action = app(BulkOperationsDemo::class);

        // Act
        $result = $action->execute();

        // Assert
        $this->assertSame(3, $result['bulk_create_result']);
        $this->assertSame(3, $result['insert_or_ignore_result']);
        $this->assertSame(5, $result['bulk_update_result']);
        $this->assertSame(5, $result['final_user_count']);
        
        // Verify that names were updated
        $this->assertDatabaseHas('users', ['name' => 'Alice Johnson (Updated)']);
        $this->assertDatabaseHas('users', ['name' => 'Bob Smith (Updated)']);
        $this->assertDatabaseHas('users', ['name' => 'Charlie Brown (Updated)']);
    }

}
