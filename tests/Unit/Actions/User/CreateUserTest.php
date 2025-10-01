<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests\Unit\Actions\User;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Notification;
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
        // Arrange
        Notification::fake();
        $createUserAction = $this->app?->make(CreateUser::class);
        assert($createUserAction instanceof CreateUser);
        $genericActionData = new CreateUserActionData('PeTr', fake()->email(), fake()->password());

        // Act
        $createUserAction->execute($genericActionData);

        // Assert
        $model = User::query()->where(['email' => $genericActionData->email])
            ->firstOrFail();

        $this->assertEquals('Petr', $model->name);
        Notification::assertSentTo($model, VerifyEmail::class);
    }

}
