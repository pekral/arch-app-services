<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests\Unit\Actions\User;

use Pekral\Arch\Examples\Actions\User\UpdateUserName;
use Pekral\Arch\Tests\Models\User;
use Pekral\Arch\Tests\TestCase;

final class UpdateUserNameActionTest extends TestCase
{

    private UpdateUserName $updateUserName;

    public function testUpdateUserName(): void
    {
        $user = User::factory()->create(['name' => 'john']);
        $newName = 'John';
        // Act
        $this->updateUserName->handle($newName, $user);
        // Assert
        $user->fresh();
        $this->assertEquals($newName, $user->name);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->updateUserName = app(UpdateUserName::class);
    }

}
