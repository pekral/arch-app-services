<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests\Unit\Actions\User;

use Pekral\Arch\Examples\Actions\User\SearchUser;
use Pekral\Arch\Tests\Models\User;
use Pekral\Arch\Tests\TestCase;

use function fake;

final class SearchUserTest extends TestCase
{

    private SearchUser $searchUser;

    public function testSearchUser(): void
    {
        // arrange
        $user = User::factory()->create();
        // act
        $foundUser = $this->searchUser->handle(['name' => $user->name, 'email' => $user->email]);
        // assert
        $this->assertNotNull($foundUser);
        $this->assertEquals($user->toArray(), $foundUser->toArray());
    }

    public function testSearchNonExistingUser(): void
    {
        // arrange
        User::factory()->create();
        // act
        $foundUser = $this->searchUser->handle(['name' => fake()->name(), 'email' => fake()->email()]);
        // assert
        $this->assertNull($foundUser);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->searchUser = app(SearchUser::class);
    }

}
