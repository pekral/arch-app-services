<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests\Unit\Actions\User;

use Pekral\Arch\Examples\Actions\User\Data\UserActionData;
use Pekral\Arch\Examples\Actions\User\UpdateUser;
use Pekral\Arch\Tests\Models\User;
use Pekral\Arch\Tests\TestCase;

use function assert;
use function fake;

final class UpdateUserTest extends TestCase
{

    /**
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function testUpdateUser(): void
    {
        // Arrange
        $user = User::factory()->create();
        $updateUserAction = $this->app?->make(UpdateUser::class);
        assert($updateUserAction instanceof UpdateUser);
        $userActionData = new UserActionData('PeTr', fake()->email(), fake()->password(), id: $user->id);

        $this->assertSame(1, $updateUserAction->execute($userActionData));
        $user = $user->fresh();
        \assert($user instanceof \Pekral\Arch\Tests\Models\User);
        $this->assertEquals('Petr', $user->name);
        $this->assertEquals($userActionData->email, $user->email);
        $this->assertEquals($userActionData->password, $user->password);
    }

}
