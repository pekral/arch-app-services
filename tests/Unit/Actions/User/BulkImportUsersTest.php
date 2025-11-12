<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests\Unit\Actions\User;

use Pekral\Arch\Examples\Actions\User\BulkImportUsers;
use Pekral\Arch\Tests\Models\User;
use Pekral\Arch\Tests\TestCase;

final class BulkImportUsersTest extends TestCase
{

    public function testExecuteWithEmptyData(): void
    {
        $action = app(BulkImportUsers::class);
        $userData = [];

        $result = $action->execute($userData);

        $this->assertSame([
            'created' => 0,
            'ignored' => 0,
            'total_processed' => 0,
        ], $result);
    }

    public function testExecuteWithNewUsers(): void
    {
        $action = app(BulkImportUsers::class);
        $userData = [
            ['name' => 'John Doe', 'email' => 'john@example.com', 'password' => 'password123'],
            ['name' => 'Jane Smith', 'email' => 'jane@example.com', 'password' => 'password456'],
        ];

        $result = $action->execute($userData);

        $this->assertSame(2, $result['total_processed']);
        $this->assertSame(2, $result['created']);
        $this->assertSame(0, $result['ignored']);
        $this->assertDatabaseHas('users', ['name' => 'John Doe', 'email' => 'john@example.com']);
        $this->assertDatabaseHas('users', ['name' => 'Jane Smith', 'email' => 'jane@example.com']);
    }

    public function testExecuteWithMixedData(): void
    {
        User::factory()->create(['email' => 'existing@example.com']);
        $action = app(BulkImportUsers::class);
        $userData = [
            ['name' => 'Existing User', 'email' => 'existing@example.com', 'password' => 'password123'],
            ['name' => 'New User', 'email' => 'new@example.com', 'password' => 'password456'],
        ];

        $result = $action->execute($userData);

        $this->assertSame(2, $result['total_processed']);
        $this->assertSame(1, $result['created']);
        $this->assertSame(1, $result['ignored']);
        $this->assertDatabaseHas('users', ['name' => 'New User', 'email' => 'new@example.com']);
    }

}
