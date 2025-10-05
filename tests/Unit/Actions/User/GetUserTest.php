<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests\Unit\Actions\User;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Pekral\Arch\Examples\Actions\User\GetUser;
use Pekral\Arch\Tests\Models\User;
use Pekral\Arch\Tests\TestCase;

use function fake;

final class GetUserTest extends TestCase
{

    private GetUser $getUser;

    public function getNonExistingUser(): void
    {
        // arrange
        User::factory()->create();
        // act & assert
        $this->expectException(ModelNotFoundException::class);
        $this->getUser->handle(['name' => fake()->name(), 'email' => fake()->email()]);
    }

    public function testGetUser(): void
    {
        // arrange
        $user = User::factory()->create();
        // act
        $foundUser = $this->getUser->handle(['name' => $user->name, 'email' => $user->email]);
        // assert
        $this->assertEquals($user->toArray(), $foundUser->toArray());
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->getUser = app(GetUser::class);
    }

}
