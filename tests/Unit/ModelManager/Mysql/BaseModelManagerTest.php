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

    protected function setUp(): void
    {
        parent::setUp();

        $this->userModelManager = app(UserModelManager::class);
    }

}
