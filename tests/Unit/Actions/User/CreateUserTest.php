<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests\Unit\Actions\User;

use Pekral\Arch\Examples\Acitons\User\CreateUser;
use Pekral\Arch\Tests\Models\User;
use Pekral\Arch\Tests\TestCase;

use function fake;

final class CreateUserTest extends TestCase
{

    /**
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function testCreateUser(): void
    {
        // Arrange

        $createUserAction = $this->app?->make(CreateUser::class);
        \assert($createUserAction instanceof \Pekral\Arch\Examples\Acitons\User\CreateUser);
        $name = fake()->name();
        $email = fake()->email();
        $password = fake()->password();

        // Act
        $createUserAction($name, $email, $password);

        // Assert
        User::query()->where([
            'email' => $email,
            'name' => $name,
        ])->firstOrFail();
    }

}
