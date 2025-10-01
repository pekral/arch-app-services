<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests\Unit\Actions\User;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Notification;
use Pekral\Arch\Examples\Actions\User\CreateUser;
use Pekral\Arch\Examples\Actions\User\Data\UserActionData;
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
        $userActionData = new UserActionData('PeTr', fake()->email(), fake()->password());

        // Act
        $createUserAction->execute($userActionData);

        // Assert
        $model = User::query()->where(['email' => $userActionData->email])
            ->firstOrFail();

        $this->assertEquals('Petr', $model->name);
        Notification::assertSentTo($model, VerifyEmail::class);
    }

}
