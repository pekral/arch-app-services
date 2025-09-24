<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests\Unit\Actions\User;

use Pekral\Arch\Examples\Acitons\User\CreateUser;
use Pekral\Arch\Examples\Acitons\User\Data\CreateUserActionData;
use Pekral\Arch\Tests\Models\User;
use Pekral\Arch\Tests\TestCase;

use function assert;
use function fake;

final class CreateUserTest extends TestCase
{

    /**
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function testCreateUser(): void
    {
        $createUserAction = $this->app?->make(CreateUser::class);
        assert($createUserAction instanceof CreateUser);
        $name = 'PeTr';
        $email = fake()->email();
        $password = fake()->password();
        $genericActionData = new CreateUserActionData($name, $email, $password);

        // Act
        $createUserAction->execute($genericActionData);

        // Assert
        $model = User::query()->where(['email' => $email])
            ->firstOrFail();

        $this->assertEquals('Petr', $model->name);
    }

}
