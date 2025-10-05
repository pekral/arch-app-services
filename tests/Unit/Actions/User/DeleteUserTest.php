<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests\Unit\Actions\User;

use Pekral\Arch\Examples\Actions\User\DeleteUser;
use Pekral\Arch\Tests\Models\User;
use Pekral\Arch\Tests\TestCase;

final class DeleteUserTest extends TestCase
{

    private DeleteUser $deleteUser;

    public function testDeleteByModel(): void
    {
        // Arrange
        $user = User::factory()->create();
        // Act
        $this->deleteUser->handle($user);
        // Assert
        $this->assertNull(User::query()->find($user->id));
    }

    public function testDeleteByParams(): void
    {
        // Arrange
        $user = User::factory()->create();
        // Act
        $this->deleteUser->handle($user);
        // Assert
        $this->assertNull(User::query()->find($user->id));
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->deleteUser = app(DeleteUser::class);
    }

}
