<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests\Unit\Actions\User;

use Pekral\Arch\Tests\Models\User;
use Pekral\Arch\Tests\TestCase;

final class UpdateUserNameActionTest extends TestCase
{

    private UpdateUserNameAction $updateUserNameAction;

    public function testUpdateUserName(): void
    {
        $user = User::factory()->create(['name' => 'john']);
        $newName = 'John';
        // Act
        $this->updateUserNameAction->handle($newName, $user);
        // Assert
        $user->fresh();
        $this->assertEquals($newName, $user->name);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->updateUserNameAction = app(UpdateUserNameAction::class);
    }

}
