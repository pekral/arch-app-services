<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests\Unit\Actions\User;

use Pekral\Arch\Examples\Actions\User\GetUsers;
use Pekral\Arch\Tests\Models\User;
use Pekral\Arch\Tests\TestCase;

use function config;
use function in_array;

final class TestGetUsers extends TestCase
{

    private GetUsers $getUsers;

    public function testGetUsers(): void
    {
        // Arrange
        $users = User::factory()->count(30)->create();
        $usersIds = $users->pluck('id')->toArray();

        // Act
        $foundUsers = $this->getUsers->handle();

        // Assert
        $this->assertCount(config()->integer('arch.default_items_per_page'), $foundUsers);
        $foundUsers->collect()->each(callback: function (mixed $user) use ($usersIds): void {
            /** @var \Pekral\Arch\Tests\Models\User $user */
            $this->assertTrue(in_array($user->id, $usersIds, true));
        });
    }

    public function testGetUsersWithFilters(): void
    {
        // Arrange
        User::factory()->count(5)->create(['name' => 'John']);
        User::factory()->count(5)->create(['name' => 'Jane']);
        
        // Act
        $foundUsers = $this->getUsers->handle(['name' => 'John']);
        
        // Assert
        $this->assertCount(5, $foundUsers);
        $foundUsers->collect()->each(callback: function (mixed $user): void {
            /** @var \Pekral\Arch\Tests\Models\User $user */
            $this->assertEquals('John', $user->name);
        });
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->getUsers = app(GetUsers::class);
    }

}
