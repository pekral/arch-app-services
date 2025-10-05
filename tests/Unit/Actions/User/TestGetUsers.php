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
        // arrange
        $users = User::factory()->count(30)->create();
        $usersIds = $users->pluck('id')->toArray();

        // act
        $foundUsers = $this->getUsers->handle();

        // assert
        $this->assertCount(config()->integer('arch.default_items_per_page'), $foundUsers);
        $foundUsers->collect()->each(callback: function (mixed $user) use ($usersIds): void {
            /** @var \Pekral\Arch\Tests\Models\User $user */
            $this->assertTrue(in_array($user->id, $usersIds, true));
        });
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->getUsers = app(GetUsers::class);
    }

}
