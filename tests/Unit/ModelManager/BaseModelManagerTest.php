<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests\Unit\ModelManager;

use Pekral\Arch\Tests\Models\User;
use Pekral\Arch\Tests\TestCase;

final class BaseModelManagerTest extends TestCase
{

    private TestUserModelManager $testUserModelManager;

    public function testCanCreateUser(): void
    {
        $userData = [
            'email' => 'john@example.com',
            'name' => 'John Doe',
            'password' => 'password123',
        ];

        $user = $this->testUserModelManager->create($userData);

        $this->assertInstanceOf(User::class, $user);
        $this->assertSame('John Doe', $user->name);
        $this->assertSame('john@example.com', $user->email);
        $this->assertSame('password123', $user->password);
        $this->assertGreaterThan(0, $user->id);
    }

    public function testCanUpdateUserByParams(): void
    {
        $user = User::factory()->create([
            'email' => 'old@example.com',
            'name' => 'Old Name',
        ]);

        $updateData = [
            'email' => 'new@example.com',
            'name' => 'New Name',
        ];

        $conditions = ['id' => $user->id];

        $updatedCount = $this->testUserModelManager->updateByParams($updateData, $conditions);

        $this->assertSame(1, $updatedCount);

        $user->refresh();
        $this->assertSame('New Name', $user->name);
        $this->assertSame('new@example.com', $user->email);
    }

    public function testCanDeleteUserByParams(): void
    {
        $user = User::factory()->create([
            'email' => 'delete@example.com',
            'name' => 'To Delete',
        ]);

        $conditions = ['id' => $user->id];

        $deleted = $this->testUserModelManager->deleteByParams($conditions);

        $this->assertTrue($deleted);
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    public function testUpdateByParamsReturnsZeroWhenNoRecordsMatch(): void
    {
        $updateData = ['name' => 'New Name'];
        $conditions = ['id' => 99_999];

        $updatedCount = $this->testUserModelManager->updateByParams($updateData, $conditions);

        $this->assertSame(0, $updatedCount);
    }

    public function testDeleteByParamsReturnsFalseWhenNoRecordsMatch(): void
    {
        $conditions = ['id' => 99_999];

        $deleted = $this->testUserModelManager->deleteByParams($conditions);

        $this->assertFalse($deleted);
    }

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->testUserModelManager = new TestUserModelManager();
    }

}
